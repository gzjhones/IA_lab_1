<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratorio 1</title>
    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>

    <!-- <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@1.5.2/dist/tf.min.js"></script> -->

    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@latest/dist/tf.min.js"></script>

    <style>
      body {
        touch-action: none; /*https://developer.mozilla.org/en-US/docs/Web/CSS/touch-action*/
      }
      #paint {
        border:3px solid #e74c3c;
        margin: auto;
        margin-bottom: 25px;
      }
      #predicted { 
        font-size: 60px;
        text-align: center;
      }
      #number {
        border: 3px solid #27ae60;
        margin: auto;
        text-align: center;
        vertical-align: middle;
      }
    </style>
</head>
<body>

    <div class="container">
        <header class="mt-4">
            <h1 class="text-center">Aplicaci&oacute;n para el reconocimiento de d&iacute;gitos</h1>
            <h2 class="text-center">Laboratorio 1</h2>
        </header>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Dibuja un d&iacute;gito</h5>
                        <p class="card-text">Dibuja un número del 0 al 9 con el cursor del mouse o el touchscrren de tu dispositivo.</p>
                        <div id="paint">
                            <canvas id="myCanvas"></canvas>
                        </div>

                        <div class="h-100 d-flex align-items-center justify-content-center">
                            <button id="clear" class="btn btn-primary">Limpiar</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Predicción del d&iacute;gito</h5>
                        <p class="card-text">El modelo exportado en formato JSON hará una predicción del patr&oacute;n dibujado.</p>
                        <div id="predicted">
                            <div id="number"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        //Opciones de visualización en diferentes dispositivos
        var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        if (isMobile) {
            $('#paint').css({'width': '60%'});
            $('#number').css({'width': '60%', 'font-size': '240px'});
        } 
        else {
            $('#paint').css({'width': '100%'});
            $('#number').css({'width': '100%', 'font-size': '300PX'});
        }

        var cw = $('#paint').width();
        $('#paint').css({'height': cw + 'px'});

        cw = $('#number').width();
        $('#number').css({'height': cw + 'px'});

        // From https://www.html5canvastutorials.com/labs/html5-canvas-paint-application/
        var canvas = document.getElementById('myCanvas');
        var context = canvas.getContext('2d');

        var compuetedStyle = getComputedStyle(document.getElementById('paint'));
        canvas.width = parseInt(compuetedStyle.getPropertyValue('width'));
        canvas.height = parseInt(compuetedStyle.getPropertyValue('height'));

        var mouse = {x: 0, y: 0};

        canvas.addEventListener('mousemove', function(e) {
            const rect = this.getBoundingClientRect();
            const mouseX = e.clientX - rect.left;
            const mouseY = e.clientY - rect.top;

            if (mouseX >= 0 && mouseX <= this.width && mouseY >= 0 && mouseY <= this.height) {
                mouse.x = mouseX;
                mouse.y = mouseY;
            }
        }, false);

        context.lineWidth = isMobile ? 30 : 25;
        context.lineJoin = 'round';
        context.lineCap = 'round';
        context.strokeStyle = '#2c3e50';

        canvas.addEventListener('mousedown', function(e) {
        context.moveTo(mouse.x, mouse.y);
        context.beginPath();
        canvas.addEventListener('mousemove', onPaint, false);
        }, false);

        canvas.addEventListener('mouseup', function() {
        // $('#number').html('<img id="spinner" src="spinner.gif"/>');
        canvas.removeEventListener('mousemove', onPaint, false);
        var img = new Image();
        img.onload = function() {
            context.drawImage(img, 0, 0, 28, 28);
            
            data = context.getImageData(0, 0, 28, 28).data;
            var input = [];
            for(var i = 0; i < data.length; i += 4) {
            input.push(data[i + 2] / 255);
            }
            predict(input);
        };
        img.src = canvas.toDataURL('image/png');
        }, false);

        var onPaint = function() {
        context.lineTo(mouse.x, mouse.y);
        context.stroke();
        };

        tf.loadGraphModel('./modeljs/model.json').then(function(model) {
        window.model = model;
        });

        // http://bencentra.com/code/2014/12/05/html5-canvas-touch-events.html
        // Set up touch events for mobile, etc
        canvas.addEventListener('touchstart', function (e) {
        var touch = e.touches[0];
        canvas.dispatchEvent(new MouseEvent('mousedown', {
            clientX: touch.clientX,
            clientY: touch.clientY
        }));
        }, false);
        canvas.addEventListener('touchend', function (e) {
        canvas.dispatchEvent(new MouseEvent('mouseup', {}));
        }, false);
        canvas.addEventListener('touchmove', function (e) {
        var touch = e.touches[0];
        canvas.dispatchEvent(new MouseEvent('mousemove', {
            clientX: touch.clientX,
            clientY: touch.clientY
        }));
        }, false);

        //Predicción basada en el modelo de TensorFlow exportado a .JSON
        var predict = function(input) {
        if (window.model) {
            window.model.predict([tf.tensor(input).reshape([1, 28, 28, 1])]).array().then(function(scores){
            scores = scores[0];
            predicted = scores.indexOf(Math.max(...scores));
            $('#number').html(predicted);
            });
        } else {
            // El modelo tarda un poco en cargarse si somos muy rápidos, esta línea hace una pausa preventiva
            setTimeout(function(){predict(input)}, 50);
        }
        }

        //Limpiar elementos del DOM
        $('#clear').click(function(){
            context.clearRect(0, 0, canvas.width, canvas.height);
            $('#number').html('');
        });
    </script>
</body>
</html>
