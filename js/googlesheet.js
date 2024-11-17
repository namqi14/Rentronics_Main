const scriptURL = 'https://script.google.com/macros/s/AKfycbwunfU8mAxSAUYwmGs-0P-ot4XrvvFBM0xy7mnJHrJPO-G9wjoLjVeUBsCzamHCLmtC7A/exec';

const form = document.forms['paymentform'];

form.addEventListener('submit', e => {
    e.preventDefault();
    fetch(scriptURL, { method: 'POST', body: new FormData(form)})
    .then(response => {
        if (response.ok) {
            alert("Thank you! Your form is submitted successfully.");
            window.location.href = "qrpage.php"; // Redirect to thank you page
        } else {
            throw new Error('Network response was not ok.');
        }
    })
    .catch(error => console.error('Error!', error.message));
});
