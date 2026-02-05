<script setup>
import { computed } from 'vue';

const props = defineProps({
  rating: {
    type: Number,
    default: 0
  },
  readOnly: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:rating']);

const setRating = (value) => {
  if (!props.readOnly) {
    emit('update:rating', value);
  }
};
</script>

<template>
  <div class="star-rating" :class="{ 'is-readonly': readOnly }">
    <span 
      v-for="star in 5" 
      :key="star"
      class="star"
      :class="{ 'filled': star <= rating }"
      @click="setRating(star)"
    >
      ★
    </span>
  </div>
</template>

<style scoped>
.star-rating {
  display: inline-flex;
  gap: 2px;
}

.star {
  font-size: 1.2rem;
  color: #e2e8f0; 
  cursor: pointer;
  transition: color 0.2s, transform 0.1s;
  line-height: 1;
}

.star.filled {
  color: #fbbf24;
}

.star-rating:not(.is-readonly) .star:hover {
  transform: scale(1.2);
}

.star-rating.is-readonly .star {
  cursor: default;
}
</style>