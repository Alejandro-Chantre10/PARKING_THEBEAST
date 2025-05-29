function mostrarAlerta(accion) {
    if (accion === 'login') {
      alert("Redirigiendo a la página de inicio de sesión...");
      window.location.href = 'inicioSesion.html'; 
    } else if (accion === 'registro') {
      alert("Redirigiendo a la página de registro...");
      window.location.href = 'registro.html';
    } else if (accion === 'reservas') {
      alert("Redirigiendo a la página de reservas...");
      window.location.href = 'views/createReservation.html';
    }
  }
  