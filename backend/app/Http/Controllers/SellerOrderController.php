<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\User;
use App\Models\SellerProfile;

class SellerOrderController extends Controller
{
    // FUNCIONES PARA OBTENER PEDIDOS
    public function getNewOrders(){
        $sellerId = Auth::id();

        $orders = $this->findAllOrders($sellerId, ['new']);

        //Se añade $this para indicar que la funcion formatOrders está en esta misma clase y Laravel no busque por todo el proyecto
        $formattedOrders = $this->formatOrders($orders);

       return response()->json($formattedOrders, 200);
    }

    public function getPendingOrders(){
        $sellerId = Auth::id();

        $orders = $this->findAllOrders($sellerId, ['pending']);

        $formattedOrders = $this->formatOrders($orders);

       return response()->json($formattedOrders, 200);
    }

        public function getAdjustedOrders(){
        $sellerId = Auth::id();

        $orders = $this->findAllOrders($sellerId, ['weight_adjusted']);

        $formattedOrders = $this->formatOrders($orders);

       return response()->json($formattedOrders, 200);
    }

    public function getReadyOrders(){
        $sellerId = Auth::id();

        $orders = $this->findAllOrders($sellerId, ['ready']);

        $formattedOrders = $this->formatOrders($orders);

       return response()->json($formattedOrders, 200);
    }

    public function getCompletedOrders(){
        $sellerId = Auth::id();

        $orders = $this->findAllOrders($sellerId, ['completed']);

        $formattedOrders = $this->formatOrders($orders);

       return response()->json($formattedOrders, 200);
    }

    public function getRejectedOrders(){
        $sellerId = Auth::id();

        $orders = $this->findAllOrders($sellerId, ['rejected']);

        $formattedOrders = $this->formatOrders($orders);

       return response()->json($formattedOrders, 200);
    }

    //FUNCION QUE COMPLEMENTA A LAS FUNCIONES ANTERIORES PARA FORMATAR LA SALIDA Y DEVOLVER LOS DATOS INTERESANTES
    public function formatOrders($orders){
        return $orders->map(function($order){
            return[
                'id' => $order->id,
                'status' => $order->status,
                'buyer_name' => $order->buyer->name,
                'total_price' => $order->total_price,
                'lines' => $order->lines->map(function($line) {
                    return [
                        'name' => $line->product->title,
                        'quantity' => $line->quantity,
                        'unit' => $line->product->unit,
                        'estimated_weight' => $line->weight_at_moment,
                        'real_weight' => $line->real_weight,
                        'line_price' => $line->price_at_moment
                        // 'image' => $line->product->image_url
                        // En caso de ser necesario, enviar tambien la url de la imagen de cada producto
                    ];
                })
            ];
        });
    }

    //FUNCIÓN PARA CAMBIAR DE 'NEW' A 'PENDING'
    public function markAsPending($orderId){
        $sellerId = Auth::id();

        $order = $this->findOneOrder($orderId, $sellerId, ['new']);

        $order->status = 'pending';

        $order->save();

        return response()->json([
            'message' => 'Pedido aceptado correctamente',
            'order_id' => $order->id
        ], 200);
    }

    // FUNCIÓN PARA PODER EDITAR UN PEDIDO PENDIENTE O QUE YA ESTÁ EDITADO. 
    // PERMITE EDITAR EL PESO REAL O LA CANTIDAD PARA CALCULAR EL PRECIO REAL DEL PEDIDO
    public function update(Request $request, $orderId){
        
        // Se valida que los datos del frontend cumplan con unas ciertas características. 
        // En caso de error, la función 'validated()' devuelve un error 422 en el frontend automáticamente.
        $validated = $request->validated([
            // Comprobar que desde el frontend se está devolviendo un objeto 'lines' y que sea un array
            'lines' => 'required|array',

            // Aquí se comprueba que en todos los objetos del array (*), el id sea numérico entero 
            // y que sea un id real en la base de datos (exists:order_lines,id)
            'lines.*.id' => 'required|integer|exists:order_lines,id',

            // Se comprueba que el peso real tenga un valor numérico y que sea mayor a 0 (gt:0).
            // Con decimal:0,2 nos aseguramos de que tenga máximo dos decimales.
            // Validamos también el máximo para evitar errores de desbordamiento en la base de datos.
            'lines.*.real_weight' => 'required|numeric|decimal:0,2|gt:0|max:999999.99',

            // Este dato corresponde con un campo oculto del frontend que se habilita si el vendedor
            // necesita poner manualmente el precio por unidad (en caso de inconsistencias con datos como el peso estimado o el precio estimado).
            // Nos aseguramos de que sea un dato mayor que 0.
            'lines.*.unit_price' => 'nullable|numeric|decimal:0,2|gt:0|max:999999.99',

            // Se recoge la cantidad para productos que se venden por unidades.
            // Debe ser entero y mayor que 0.
            'lines.*.quantity' => 'nullable|numeric|integer|gt:0|max:999999'
        ]);

        $sellerId = Auth::id();

        // Se pretende actualizar aquel pedido que esté pendiente o que ya haya sido editado y se quiera volver a editar.
        $order = $this->findOneOrder($orderId, $sellerId, ['pending', 'weight_adjusted']);

        // Con Transaction, nos aseguramos de que en caso de haber un fallo (luz, servidor), los datos no queden incompletos.
        // O se ejecuta toda la función o no se hace nada.
        DB::Transaction(function () use ($order, $validated){
            $totalPrice = 0;
            
            // Se utiliza la función collect() para convertir el array simple del request en una Colección de Laravel.
            // Esto nos permite usar funciones avanzadas como 'firstWhere' que los arrays nativos de PHP no tienen.
            $orderLines = collect($validated['lines']);

            // Se recorren las líneas del pedido guardadas en la base de datos ($order->lines).
            // Esto es crucial por seguridad: si un usuario malintencionado intenta enviar IDs de otros pedidos
            // en el request, el bucle los ignorará porque solo iteramos sobre lo que realmente pertenece a este pedido.
            foreach($order->lines as $line){
                
                // La variable data es un array asociativo, que permite acceder a los campos de dentro directamente mediante el nombre de estos campos
                // sin necesidad de acceder a ellos mediante posición
                $data = $orderLines->firstWhere('id', $line->id);

                // Solo se procesan aquellas líneas del pedido que hayan sido editadas
                if($data){
                    $realWeight = $data['real_weight'];
                    
                    // --- BLOQUE 1: GESTIÓN DE STOCK ---
                    
                    // CASO A: Producto vendido por PESO (KG)
                    if($line->product->unit === 'kg'){
                        //Para los productos que funcionan por peso, el cliente hace la demanda de una cantidad de peso y se guarda en el
                        //campo de weight_at_moment del pedido, y en el campo quantity se dejaría marcado como 1 para evitar posibles fallos de cálculo
                        //que puedan pasar
                        $weightDifference = $realWeight - $line->weight_at_moment;

                        // Si la diferencia es positiva, estamos quitando stock. Verificamos que haya suficiente.
                        if($weightDifference > 0 && $weightDifference > $line->product->stock){
                            abort(400, "No se puede establecer el peso real debido a que supera al stock del producto. Faltan " . ($weightDifference - $line->product->stock) . "kg");
                        }

                        // Con decrement, se opera automáticamente: 
                        // - Si $weightDifference es positivo (ej: 0.5), resta stock.
                        // - Si es negativo (ej: -0.5), al restar un negativo, suma stock (devuelve al almacén).
                        $line->product->decrement('stock', $weightDifference);
                        
                        // Actualizamos solo el peso en la línea
                        $line->real_weight = $realWeight;
                    
                    // CASO B: Producto vendido por UNIDAD (Quantity)
                    } elseif(isset($data['quantity'])){
                        //Para aquellos productos que funcionan por unidad, la cantidad se guarda en un campo quantity.
                        //Para estos productos, el campo de weight_at_moment serviría como información adicional, pero no para calcular el precio o stock
                        $newQuantity = $data['quantity'];
                        $qtyDifference = $newQuantity - $line->quantity;

                        // Verificamos stock si pide más unidades de las que había
                        if($qtyDifference > 0 && $qtyDifference > $line->product->stock){
                             abort(400, "No hay suficiente stock de " . $line->product->title . ". Solo quedan " . ($line->product->stock) . " unidades.");
                        }

                        // Ajustamos stock y actualizamos la cantidad en la línea
                        $line->product->decrement('stock', $qtyDifference);
                        $line->quantity = $newQuantity; 
                        
                        // Aunque el peso no sea necesario ya que el precio se calcula por unidad, igualmente es importante guardar el peso real del pedido, por si en caso
                        // de contratar una agencia de repartos externa que cobran por kilo o para el transporte, es importante conocer este dato
                        $line->real_weight = $realWeight; 
                    }

                    // --- BLOQUE 2: CÁLCULO DE PRECIO UNITARIO ---
                    
                    $unitPrice = 0;

                    // Opción 1: Intentar deducir el precio del historial (Precio Antiguo / Cantidad Antigua)
                    if($line->product->unit === 'kg' && $line->price_at_moment > 0 && $line->weight_at_moment > 0){
                        $unitPrice = $line->price_at_moment / $line->weight_at_moment;

                    // Opción 2: Usar precio manual
                    // Si el cálculo falló o dio 0, miramos si el vendedor introdujo el precio manual.
                    }elseif($unitPrice <= 0 && isset($data['unit_price'])){
                        $unitPrice = $data['unit_price'];

                    // Si tras ambos intentos no tenemos precio, es imposible cobrar.
                    }else{
                        abort(400, "Faltan datos para calcular el precio de " . ($line->product->title) . ". Por favor, introduce el precio por unidad.");
                    }

                    // --- BLOQUE 3: CÁLCULO FINAL Y GUARDADO ---

                    // Calculamos el total de la línea según su tipo (Kilos * Precio o Unidades * Precio)
                    if ($line->product->unit === 'kg') {
                        $totalLinePrice = $unitPrice * $realWeight;
                    } else {
                        // Usamos la cantidad (que puede ser la nueva si entró en el elseif de arriba, o la vieja si no)
                        $totalLinePrice = $unitPrice * $line->quantity;
                    }

                    $line->price_at_moment = $totalLinePrice;
                    $line->save(); // Guardamos los cambios en la línea (BD)
                    
                    $totalPrice += $totalLinePrice; // Sumamos al acumulador del pedido

                } else {
                    // Si la línea NO fue editada por el usuario, sumamos su precio antiguo al total
                    // para no perder el valor de los productos no tocados.
                    $totalPrice += $line->price_at_moment;
                }                
            }

            // Actualizamos el precio total del pedido completo una sola vez al final
            $order->total_price = $totalPrice;
            
            // Actualizamos el estado si estaba pendiente
            if($order->status === 'pending'){
                $order->status = 'weight_adjusted';
            }
            
            $order->save();
        });

        return response()->json(['message' => 'Pedido actualizado correctamente', 'total' => $order->total_price]);
    }


    //---------REVISAR FUNCIONES----------------------------
    public function markAsReady($orderId){
        $sellerId = Auth::id();

        $order = $this->findOneOrder($orderId, $sellerId, ['pending', 'weight_adjusted']);

        // Faltaría la opción de configurar un punto de recogida, aunque no sé si eso se hace aquí o en el controlador de puntos de recogida

        $order->status = 'ready';
        $order->save();

        return response()->json(['message' => 'Pedido marcado como listo para recoger', 'order' => $order]);
    }

    public function markAsCompleted($orderId){
        $sellerId = Auth::id();

        $order = $this->findOneOrder($orderId, $sellerId, ['ready']);

        // Añadir una validación para confirmar que se ha completado el pedido

        $order->status = 'completed';
        $order->save();

        return response()->json(['message' => 'Pedido completado y entregado', 'order' => $order]);
    }

    // Esta función ahora sirve tanto para el VENDEDOR (Rechazar) como para el COMPRADOR (Cancelar)
    public function cancelOrRejectOrder(Request $request, $orderId){

        // 1. VALIDACIÓN
        // El motivo sigue siendo obligatorio. Si es el cliente, puede poner "Ya no lo quiero".
        $request->validate([
            'rejection_reason' => 'required|string|min:5|max:255'
        ]);

        $userId = Auth::id();

        // 2. BÚSQUEDA DEL PEDIDO (Ya no usamos findOneOrder porque esa era solo para vendedores)
        // Buscamos el pedido por ID y cargamos las líneas para la devolución de stock
        $order = Order::with('lines.product')->find($orderId);

        if (!$order) {
            abort(404, 'Pedido no encontrado');
        }

        // 3. DETERMINAR EL ROL Y LOS PERMISOS
        $isBuyer = ($order->buyer_id === $userId);
        $isSeller = ($order->seller_id === $userId);

        // Si el usuario no es ni el comprador ni el vendedor de este pedido -> FUERA
        if (!$isBuyer && !$isSeller) {
            abort(403, 'No tienes permiso para gestionar este pedido.');
        }

        // 4. REGLAS DE ESTADO SEGÚN QUIÉN SEAS
        if ($isBuyer) {
            // REGLA COMPRADOR: Solo puede cancelar si está en 'new'.
            // Si el vendedor ya lo aceptó ('pending'), el comprador debe contactar por chat/teléfono.
            if ($order->status !== 'new') {
                abort(400, 'No puedes cancelar el pedido porque ya está siendo preparado. Contacta con el vendedor.');
            }
            $newStatus = 'cancelled'; // Estado específico para saber que fue el cliente
        } 
        elseif ($isSeller) {
            // REGLA VENDEDOR: Puede rechazar en casi cualquier estado (menos si ya se entregó/completó).
            if (in_array($order->status, ['completed', 'rejected', 'cancelled'])) {
                abort(400, 'Este pedido ya está finalizado o cancelado.');
            }
            $newStatus = 'rejected'; // Estado específico para saber que fue el vendedor
        }

        // 5. TRANSACCIÓN (Devolución de Stock + Cambio de Estado)
        DB::transaction(function () use ($order, $request, $newStatus) {
            
            // LÓGICA DE DEVOLUCIÓN DE STOCK
            // Como asumimos que el stock se resta SIEMPRE al crear el pedido ('new'),
            // SIEMPRE debemos devolverlo, sea quien sea el que cancele.
            
            foreach ($order->lines as $line) {
                
                if ($line->product->unit === 'kg') {
                    // Si el pedido es 'new', real_weight será 0, así que devolvemos el estimado.
                    // Si el pedido ya se pesó, real_weight tendrá valor y devolvemos eso.
                    $weightToReturn = ($line->real_weight > 0) ? $line->real_weight : $line->weight_at_moment;
                    
                    if($weightToReturn > 0) {
                        $line->product->increment('stock', $weightToReturn);
                    }

                } else {
                    // Producto por unidad
                    $qtyToReturn = $line->quantity;
                    if($qtyToReturn > 0) {
                        $line->product->increment('stock', $qtyToReturn);
                    }
                }
            }

            // Guardamos el nuevo estado ('cancelled' o 'rejected')
            $order->status = $newStatus;
            
            // Guardamos el motivo. Es útil saber por qué el cliente canceló.
            // Asegúrate de tener una columna 'cancellation_reason' o usar la misma 'rejection_reason'
            // $order->rejection_reason = $request->rejection_reason; 

            $order->save();
        });

        $message = $isBuyer ? 'Has cancelado tu pedido correctamente.' : 'Has rechazado el pedido correctamente.';

        return response()->json(['message' => $message, 'status' => $newStatus]);
    }

    //----------------------------------------------------------------------------------------------------------


    //FUNCIÓN QUE PERMITE LOCALIZAR PEDIDOS DE UN VENDEDOR Y ESTADO/S CONCRETO/S (EN CASO DE NO ENCONTRAR NINGUNO SE NOTIFICARÍA)
    public function findOneOrder($orderId, $sellerId, array $status){
        //La función get obtiene todos los registros y los guarda en una colección. Con first, 
        //se obtiene el primer registro directamente (sólo queremos obtener un pedido concreto) y
        //no se guarda en formato de colección, lo que ahorra trabajo, ya que entonces habría que
        //acceder al objeto de dentro de la colección
        $order = Order::where('id', $orderId)
                    ->where('seller_id', $sellerId)
                    ->whereIn('status', $status)
                    ->first();

        if(!$order){
            abort(404, 'No se ha encontrado el pedido');
        }

        return $order;
    }

    //FUNCIÓN PARA ENCONTRAR TODOS LOS PEDIDOS QUE SE CORRESPONDAN CON UN VENDEDOR Y SEAN DE UNO
    //O VARIOS ESTADOS DEFINIDOS
    public function findAllOrders($sellerId, array $status){
        $orders = Order::with(['buyer', 'lines.product'])
                        ->where('seller_id', $sellerId)
                        ->whereIn('status', $status)
                        ->get();

        if(!$orders){
            abort(404, 'No se ha encontrado ningún pedido');
        }

        return $orders;
    }

}
