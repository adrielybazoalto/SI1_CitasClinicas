// CU14 · Dibuja los códigos QR de la página: <canvas data-qr="texto"></canvas>
import QRCode from 'qrcode';

document.querySelectorAll('canvas[data-qr]').forEach((canvas) => {
    QRCode.toCanvas(canvas, canvas.dataset.qr, { width: 220, margin: 1, color: { dark: '#2E0B31', light: '#FFFFFF' } });
});
