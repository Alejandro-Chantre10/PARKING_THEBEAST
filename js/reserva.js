function Reserva(accion) {
    if (accion === 'editar') {
      window.location.href = 'updateReservation.html'; 
    } else if (accion === 'eliminar') {
      window.location.href = 'deleteReservation.html'; 
    }
    else if (accion === 'descargar') {
      window.location.href = 'registro.html'; 
  }
}
  