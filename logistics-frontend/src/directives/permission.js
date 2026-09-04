export default {
  mounted(element, binding) {
    const roles = Array.isArray(binding.value) ? binding.value : [binding.value];
    const userRoles = JSON.parse(localStorage.getItem('user_roles') || '[]');
    if (!roles.some((role) => userRoles.includes(role))) element.remove();
  },
};