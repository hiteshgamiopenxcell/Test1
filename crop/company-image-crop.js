function cropImage(dimensions) {
    class Uploader {

        constructor(options) {
            if (!options.input) {
                throw '[Uploader] Missing input file element.';
            }
            this.fileInput = options.input;
            this.types = options.types || ['gif', 'jpg', 'jpeg', 'png'];
        }

        listen(resolve, reject) {
            this.fileInput.onchange = (e) => {
                e.preventDefault();
                if (!this.fileInput.files || this.fileInput.files.length !== 1) {
                    reject('[Uploader:listen] Select only one file.');
                }

                let file = this.fileInput.files[0];
                let reader = new FileReader();
                if (!this.validFileType(file.type)) {
                    reject(`[Uploader:listen] Invalid file type: ${file.type}`);
                } else {
                    reader.readAsDataURL(file);
                    reader.onload = (e) => resolve(e.target.result);
                }
            };
        }

        validFileType(filename) {
            let extension = filename.split('/').pop().toLowerCase();
            return this.types.includes(extension);
        }
    }

    function squareContains(square, coordinate) {
        return coordinate.x >= square.pos.x && coordinate.x <= square.pos.x + square.size.x && coordinate.y >= square.pos.y && coordinate.y <= square.pos.y + square.size.y;
    }

    class Cropper {

        constructor(options) {
            // console.log(options);
            if (!options.size) {
                throw 'Size field in options is required';
            }
            if (!options.canvas) {
                throw 'Could not find image canvas element.';
            }
            if (!options.preview) {
                throw 'Could not find preview canvas element.';
            }

            this.imageCanvas = options.canvas;
            this.previewCanvas = options.preview;
            this.c = this.imageCanvas.getContext("2d");

            this.limit = options.limit || 900;
            this.crop = {
                size: {
                    x: options.size.width,
                    y: options.size.height
                },
                pos: {
                    x: 0,
                    y: 0
                },
                handleSize: 10
            };

            this.previewCanvas.width = options.size.width;
            this.previewCanvas.height = options.size.height;

            this.boundDrag = this.drag.bind(this);
            this.boundClickStop = this.clickStop.bind(this);
        }

        setImageSource(source) {
            this.image = new Image();
            this.image.src = source;
            this.image.onload = (e) => {
                this.render();
                this.imageCanvas.onmousedown = this.clickStart.bind(this);
            }
        }

        export (img) {
            img.setAttribute('src', this.previewCanvas.toDataURL());
        }

        render() {
            this.c.clearRect(0, 0, this.imageCanvas.width, this.imageCanvas.height);
            this.displayImage();
            this.preview();
            this.drawCropWindow();
        }

        clickStart(e) {
            const position = {
                x: e.offsetX,
                y: e.offsetY
            };
            this.lastEvent = {
                position: position,
                resizing: this.isResizing(position),
                moving: this.isMoving(position)
            };
            this.imageCanvas.addEventListener('mousemove', this.boundDrag);
            this.imageCanvas.addEventListener('mouseup', this.boundClickStop);
        }

        clickStop(e) {
            this.imageCanvas.removeEventListener("mousemove", this.boundDrag);
            this.imageCanvas.removeEventListener("mouseup", this.boundClickStop);
        }

        isResizing(coord) {
            const size = this.crop.handleSize;
            const handle = {
                pos: {
                    x: this.crop.pos.x + this.crop.size.x - size / 2,
                    y: this.crop.pos.y + this.crop.size.y - size / 2
                },
                size: {
                    x: size,
                    y: size
                }
            };
            return squareContains(handle, coord);
        }

        isMoving(coord) {
            return squareContains(this.crop, coord);
        }

        drag(e) {
            const position = {
                x: e.offsetX,
                y: e.offsetY
            };
            const dx = position.x - this.lastEvent.position.x;
            const dy = position.y - this.lastEvent.position.y;
            if (this.lastEvent.resizing) {
                this.resize(dx, dy);
            } else if (this.lastEvent.moving) {
                this.move(dx, dy);
            }
            this.lastEvent.position = position;
            this.render();
        }

        resize(dx, dy) {
            let handle = {
                x: this.crop.pos.x + this.crop.size.x,
                y: this.crop.pos.y + this.crop.size.y
            };
            const amount = Math.abs(dx) > Math.abs(dy) ? dx : dy;
            if (this.inBounds(handle.x + amount, handle.y + amount)) {
                this.crop.size.x += amount;
                this.crop.size.y += amount;
            }
        }

        move(dx, dy) {
            const tl = {
                x: this.crop.pos.x,
                y: this.crop.pos.y
            };
            const br = {
                x: this.crop.pos.x + this.crop.size.x,
                y: this.crop.pos.y + this.crop.size.y
            };
            if (this.inBounds(tl.x + dx, tl.y + dy) &&
                this.inBounds(br.x + dx, tl.y + dy) &&
                this.inBounds(br.x + dx, br.y + dy) &&
                this.inBounds(tl.x + dx, br.y + dy)) {
                    this.crop.pos.x += dx;
                this.crop.pos.y += dy;
            }
        }

    displayImage() {
        var iWidth = this.image.width;
        var iHeight = this.image.height;
        // if((iWidth == 440 && iHeight == 560) || (iWidth == 300 && iHeight == 200)) {
            if(iWidth == 56 && iHeight == 30) {
                this.imageCanvas.width = iWidth;
                this.imageCanvas.height = iHeight;
                this.c.drawImage(this.image, 0, 0, iWidth, iHeight);
            } else {
                const ratio = this.limit / Math.max(iWidth, iHeight);
                this.image.width *= ratio;
                this.image.height *= ratio;
                this.imageCanvas.width = this.image.width;
                this.imageCanvas.height = this.image.height;
                this.c.drawImage(this.image, 0, 0, iWidth, iHeight);
            }
        }

        drawCropWindow() {
            const pos = this.crop.pos;
            const size = this.crop.size;
            const radius = this.crop.handleSize / 2;
            this.c.strokeStyle = 'red';
            this.c.fillStyle = 'red';
            this.c.strokeRect(pos.x, pos.y, size.x, size.y);
            const path = new Path2D();
            path.arc(pos.x + size.x, pos.y + size.y, radius, 0, Math.PI * 2, true);
            this.c.fill(path);
        }

        preview() {
            const pos = this.crop.pos;
            const size = this.crop.size;
            const imageData = this.c.getImageData(pos.x, pos.y, size.x, size.y);
            this.previewCanvas.width = size.x;
            this.previewCanvas.height = size.y;
            if (!imageData) {
                return false;
            }
            const ctx = this.previewCanvas.getContext('2d');
            ctx.clearRect(0, 0, this.previewCanvas.width, this.previewCanvas.height);
            ctx.drawImage(this.imageCanvas,
                pos.x, pos.y,
                size.x, size.y,
                0, 0,
                this.previewCanvas.width, this.previewCanvas.height);
        }

        inBounds(x, y) {
            return squareContains({
                pos: {
                    x: 0,
                    y: 0
                },
                size: {
                    x: this.imageCanvas.width,
                    y: this.imageCanvas.height
                }
            }, {
                x: x,
                y: y
            });
        }
    }

    try {
        var uploader = new Uploader({
            input: document.querySelector('.js-fileinput'),
            types: ['gif', 'jpg', 'jpeg', 'png']
        });

        var editor = new Cropper({
            size: dimensions,
            canvas: document.querySelector('.js-editorcanvas'),
            preview: document.querySelector('.js-previewcanvas')
        });

        if (uploader && editor) {
            uploader.listen(editor.setImageSource.bind(editor), (error) => {
                throw error;
            });
        }

        var img = document.createElement('img');
        img.id = "croppedimage";
        /*img.setAttribute("width", "75");*/
        document.body.appendChild(img);
        $(".edited-image").html(img);
        document.querySelector('.js-export').onclick = (e) => editor.export(img);

    } catch (error) {
        console.log(error.message);
    }
}