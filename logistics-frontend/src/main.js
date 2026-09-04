import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';

// Updated import path:
import './assets/main.css'; 
import permission from './directives/permission';

const app = createApp(App);

app.use(createPinia());
app.use(router);
app.directive('role', permission);

app.mount('#app');