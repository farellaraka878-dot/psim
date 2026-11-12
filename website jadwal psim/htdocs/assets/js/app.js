document.querySelectorAll('.star-picker button').forEach((button) => {
    button.addEventListener('mouseenter', () => {
        const score = Number(button.value);
        document.querySelectorAll('.star-picker button').forEach((star) => {
            star.classList.toggle('is-highlighted', Number(star.value) <= score);
        });
    });
});

document.querySelectorAll('.star-picker').forEach((picker) => {
    picker.addEventListener('mouseleave', () => {
        picker.querySelectorAll('button').forEach((star) => star.classList.remove('is-highlighted'));
    });
});
