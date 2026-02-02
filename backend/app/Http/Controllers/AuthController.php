<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    // ... (Login y Register se quedan igual) ...

    public function login(Request $request) {
        // Se validan el email y la contraseña. Si algo falla, Laravel
        // responde automáticamente
        $request->validate([
            // email requerido y con un formato de email
            'email' => 'required|email',
            // contraseña requerida
            'password' => 'required',
        ]);
        // Se obtiene el usuario a partir del email
        $user = User::where('email', $request->email)->first();
        // En caso de no encontrar ningún usuario o en caso de que se haya
        // encontrado pero la contraseña no coincida, devolver un error
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'error' => ['Las credenciales son incorrectas.'],
            ]);
        }
        // Si no hay problemas, se crea un token de acceso para que el usuario
        // pueda navegar por la web. Se le otorga el token en texto plano al usuario
        // y en la base de datos se almacena encriptado para mayor seguridad 
        // (en caso de que alguien accediera a la base de datos, no podría hacer
        // nada con los token)
        $token = $user->createToken('auth_token')->plainTextToken;

        // Se devuelve una respuesta en formato JSON
        return response()->json([
            // Mensaje informativo de que todo ha ido bien
            'message' => 'Login exitoso',
            // El token de acceso para el usuario
            'access_token' => $token,
            // El tipo de token, identificado al portador
            'token_type' => 'Bearer',
            // El usuario que se ha logeado para poder cargar sus datos
            'user' => $user,
        ], 200);
    }

    public function register(Request $request) {
        // Se validan los datos entrantes
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            // se valida que el email sea único en la tabla 'users'
            'email' => 'required|string|email|max:255|unique:users',
            // la propiedad 'confirmed' indica que el campo password ha de coincidir
            // con un campo llamado 'password_confirmation', para asegurarse de que la contraseña
            // que se quiere escribir es correcta
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Creación del usuario: se guarda el usuario en la base de datos y
        // se guardan los datos en una variable para poder devolverlos a Vue
        // y poder mostrar la información del usuario
        $user = User::create([
            'name' => $validated['name'],
            'surname' => $validated['surname'],
            'email' => $validated['email'],
            // Con Hash::make(), se crea el usuario con la contraseña que 
            // ha introducido, encriptada
            'password' => Hash::make($validated['password']),
        ]);

        // Al igual que en el login, se crea el token y se devuelve una respuesta
        // para poder gestionar la información del usuario y el token
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'message' => 'Usuario registrado exitosamente',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ], 201); // El código 201 indica que se ha creado una instancia correctamente
    }

    public function logout(Request $request) {
        // Se accede al usuario que ha iniciado sesión, y se borra
        // el registro del token que estaba utilizando para navegar por la web
        // en la base de datos para que deje de ser válido
        // (también se borra desde la web en la vista del perfil de Vue)
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    public function updateProfile(Request $request) {
        // Se obtiene la instancia del usuario que ha iniciado sesión
        $user = $request->user();

        // Se validan los datos que introduce
        $request->validate([
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            // Se valida que el email ha de ser único en la tabla users, que ocupa
            // el campo email (unique:users,email,). Además, a la hora de editar tu perfil,
            // si no modificas el email, te saltaría un error, porque Laravel se pondría a buscar
            // tu email en la base de datos, y como existe, causaría problemas. Para ello,
            // se añade el trozo '. $user->id,' que sirve para excluir de la búsqueda el id
            // del usuario que ha iniciado sesión. De esta manera, se soluciona este problema
            // y además tampoco podrías ponerte el email de otro usuario
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            // Se valida la foto de perfil. Se valida que sea una imagen, que
            // coincida con las extensiones que hay abajo y que pese máximo 2MB
            'avatar_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', 
        ]);

        // Se valida si el usuario manda un archivo
        // en el campo de la foto de perfil al actualizar su perfil
        if ($request->hasFile('avatar_url')) {
            // Si es que sí, se valida si el usuario tenía previamente una foto de perfil
            if ($user->avatar_url) {
                // Si es así, se borra la antigua foto de la carpeta 'public' 
                // (donde se almacenan las fotos de perfil. La ruta de la carpeta 
                // se encuentra en storage/app/public/avatars)
                Storage::disk('public')->delete($user->avatar_url);
            }
            
            // Se guarda en una variable, la ruta donde se va a guardar la foto.
            // Primero, se obtiene el archivo del campo de Vue donde se aloja la foto
            // (avatar_url), luego, se almacena en la carpeta 'avatars' dentro
            // de la carpeta 'public' (ruta mencionada anteriormente). Además,
            // también modifica el nombre de los archivos para que en caso de que
            // dos usuarios guarden una foto con el mismo nombre, no se sobreescriban
            $path = $request->file('avatar_url')->store('avatars', 'public');
            
            // Se registra la ruta de la foto de perfil en el registro del usuario
            // de la base de datos
            $user->avatar_url = $path; 
        }

        // Se modifican los campos de la base de datos con los nuevos valores introducidos
        $user->name = $request->name;
        $user->surname = $request->surname;
        $user->email = $request->email;
        
        // Se guardan los cambios
        $user->save();

        // Se devuelve un mensaje de respuesta
        return response()->json([
            'message' => 'Perfil actualizado correctamente',
            'user' => $user
        ]);
    }
}