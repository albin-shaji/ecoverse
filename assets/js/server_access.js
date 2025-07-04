document.getElementById('serverHomeLink').addEventListener('click', function(event) {
    event.preventDefault(); // Prevent the default link behavior

    Swal.fire({
      title: 'Are you sure you want to Access the Server Homepage?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Yes',
      cancelButtonText: 'No'
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = '/index'; // Navigate to the server homepage
      }
      // If result.isConfirmed is false (No was clicked), do nothing,
      // and the user remains on the current page.
    });
  });