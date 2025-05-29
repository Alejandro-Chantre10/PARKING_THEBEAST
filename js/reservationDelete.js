// verReservaAdm.js

let reservaSeleccionada = null;
let filaSeleccionada = null;

// Mostrar el modal de confirmación con el número de reserva y la fila correspondiente
function showDeleteModal(reservaId, buttonElement) {
  reservaSeleccionada = reservaId;
  filaSeleccionada = buttonElement.closest("tr");

  const modal = document.getElementById("deleteModal");
  const texto = document.getElementById("modalText");

  texto.innerHTML = `¿Estás seguro de eliminar la reserva <strong>${reservaId}</strong>?`;
  modal.style.display = "flex";
}

// Ocultar el modal
function closeModal() {
  document.getElementById("deleteModal").style.display = "none";
}

// Eliminar la fila visualmente
function eliminarFila() {
  if (filaSeleccionada) {
    filaSeleccionada.remove();
    alert("Reserva " + reservaSeleccionada + " eliminada.");
  }
  closeModal();
}

// Esperar al DOM y agregar funcionalidad al botón "Eliminar"
document.addEventListener("DOMContentLoaded", function () {
  const eliminarBtn = document.querySelector(".btn-delete");
  eliminarBtn.addEventListener("click", eliminarFila);
});