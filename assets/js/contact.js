    document.getElementById('contactLink').addEventListener('click', function(event) {
      Swal.fire({
        title: 'Connect with Albin!',
        text: 'You are just one click away from connecting with me directly on the main server. Shall we proceed?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes',
        cancelButtonText: 'No',
        customClass: {
          container: 'custom-swal-container' // Add a custom class for styling if needed
        }
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = '/home/contact.html';
        }
      });
    });