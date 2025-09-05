document.addEventListener('DOMContentLoaded', function() {
    var container = document.getElementById('ysp-container');
    if (container) {
        var square = document.createElement('div');
        square.id = 'ysp-square';
        // Set size from settings if available, fallback to data attributes
        var height = 100;
        var width = 100;
        if (typeof yspSettings !== 'undefined' && yspSettings.height && yspSettings.width) {
            height = yspSettings.height;
            width = yspSettings.width;
        } else {
            // fallback to data attributes
            if (container.dataset.height) height = container.dataset.height;
            if (container.dataset.width) width = container.dataset.width;
        }
        console.log('Square height:', height, 'width:', width);
        square.style.height = height + 'px';
        square.style.width = width + 'px';
        container.appendChild(square);
    }
});
// hello world ok so this is working + new commit _nwo