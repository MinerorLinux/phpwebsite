const canvas = document.querySelector('.matrix canvas');
const ctx = canvas.getContext('2d');

// Set canvas size to full window size
canvas.width = window.innerWidth;
canvas.height = window.innerHeight;

// Letters array and font size
const letters = Array.from({ length: Math.floor(canvas.width / 10) }, () => 0);
const fontSize = 10;

// Draw function to render the matrix effect
function draw() {
    // Clear the canvas with a semi-transparent black rectangle
    ctx.fillStyle = 'rgba(0, 0, 0, 0.05)';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // Set the font style and color
    ctx.fillStyle = '#00ff00';
    ctx.font = `${fontSize}px monospace`;

    // Draw each letter
    letters.forEach((y, index) => {
        const text = String.fromCharCode(65 + Math.random() * 33);
        const x = index * fontSize;
        ctx.fillText(text, x, y);
        letters[index] = y > canvas.height + Math.random() * 1e4 ? 0 : y + fontSize;
    });

    // Request the next frame
    requestAnimationFrame(draw);
}

// Start the animation
requestAnimationFrame(draw);

// Adjust canvas size on window resize
window.addEventListener('resize', () => {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    letters.length = Math.floor(canvas.width / fontSize);
    letters.fill(0);
});