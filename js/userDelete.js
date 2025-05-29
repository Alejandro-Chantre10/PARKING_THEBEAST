function mostrarModal(nombre) {
    document.getElementById("mensajeConfirmacion").innerText =
      `Estás seguro de eliminar usuario ${nombre}?`;
    document.getElementById("modalOverlay").style.display = "flex";
  }
  
  function cerrarModal() {
    document.getElementById("modalOverlay").style.display = "none";
  }
  
  function confirmarEliminacion() {
    // Aquí colocas la lógica real para eliminar el usuario
    alert("Usuario eliminado.");
    cerrarModal();
  }
  