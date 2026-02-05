<script setup>
import { computed } from 'vue';
import StarRating from './StarRating.vue'; 

const props = defineProps({
  review: {
    type: Object,
    required: true
  }
});
ç
const formattedDate = computed(() => {
  if (!props.review.date) return '';
  const date = new Date(props.review.date);
  return new Intl.DateTimeFormat('es-ES', { 
    day: 'numeric', 
    month: 'short', 
    year: 'numeric' 
  }).format(date);
});
const avatarUrl = computed(() => {
  const url = props.review.author?.avatar;
  if (!url) return 'https://via.placeholder.com/40?text=U'; 
  return url.startsWith('http') ? url : `http://localhost:8000/storage/${url}`;
});
</script>

<template>
  <div class="review-card">
    <div class="review-header">
      <div class="author-info">
        <img :src="avatarUrl" alt="Avatar" class="avatar">
        <div class="author-details">
          <span class="author-name">{{ review.author?.name || 'Usuario Anónimo' }}</span>
          <StarRating :rating="review.rating" :readOnly="true" class="mini-stars" />
        </div>
      </div>
      
      <span class="review-date">{{ formattedDate }}</span>
    </div>

    <div class="review-body">
      <p>{{ review.comment }}</p>
    </div>
  </div>
</template>

<style scoped>
.review-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 15px;
  margin-bottom: 15px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.02);
  transition: transform 0.2s;
}

.review-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}

.review-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 10px;
}

.author-info {
  display: flex;
  gap: 12px;
  align-items: center;
}

.avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #f1f5f9;
}

.author-details {
  display: flex;
  flex-direction: column;
}

.author-name {
  font-weight: 600;
  color: #1e293b;
  font-size: 0.95rem;
}

.mini-stars {
  font-size: 0.8rem;
}

.review-date {
  font-size: 0.8rem;
  color: #94a3b8;
}

.review-body p {
  margin: 0;
  color: #475569;
  font-size: 0.95rem;
  line-height: 1.5;
}
</style>