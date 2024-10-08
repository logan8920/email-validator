<!DOCTYPE html>
<html>
<head>
	<title></title>
</head>
<body>
	<style type="text/css">
		@import url('https://fonts.googleapis.com/css2?family=Ubuntu:wght@400&display=swap');

		* {
		  font-family: 'Ubuntu', sans-serif;
		}

		body {
		  background-color: #222;
		  color: #fff;
		}

		#drop-area {
		  border: 2px dashed #aaa;
		  padding: 20px;
		  text-align: center;
		  display: flex;
		  justify-content: center;
		  align-items: center;
		  background-color: #333;
		  border-radius: 8px;
		  min-height: 2in;
		  flex-direction: column;
		  gap: 10px;
		  cursor: pointer;
		}

		.drop-text {
		  font-size: 18px;
		  color: #888;
		}

		#dropped-content {
		  width: calc(100% - 23px);
		  height: 200px;
		  min-height: 69px;
		  max-height: 312px;
		  margin-top: 20px;
		  font-size: 16px;
		  padding: 10px;
		  border: 1px solid #555;
		  resize: vertical;
		  background-color: #444;
		  color: #fff;
		  outline: none;
		  border-radius: 8px;
		}

		#dropped-content::-webkit-scrollbar {
		  width: 8px;
		}

		#dropped-content::-webkit-scrollbar-track {
		  background-color: #444;
		}

		#dropped-content::-webkit-scrollbar-thumb {
		  background-color: #888;
		  border-radius: 4px;
		}

		#dropped-content::-webkit-scrollbar-thumb:hover {
		  background-color: #aaa;
		}

		.drop-icon i {
		  color: #888;
		  font-size: 4.5rem;
		}

		#chars {
		  color: #888;
		  float: right;
		}

		#spellcheck {
		  display: flex;
		  align-items: center;
		  gap: 5px;
		}

		#outer-dot {
		  background-color: #007bff;
		  width: 3rem;
		  height: 1.5rem;
		  border-radius: 100px;
		  cursor: pointer;
		  display: flex;
		  align-items: center;
		  transition: background 200ms;
		}

		#dot {
		  background-color: #eee;
		  width: 1rem;
		  height: 1rem;
		  border-radius: 50%;
		  margin: 0 5px;
		  transform: translateX(22px);
		  transition: transform 200ms;
		}
	</style>
	<link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.4.0/css/all.css">
	<form action="/upload" method="POST" enctype="multipart/form-data">
		<div id="drop-area" for="excel">
		  <div class="drop-icon">
		    <i class="fa-light fa-file-upload"></i>
		    <input type="file" id="excel" style="display: none;" name="excel" />
		  </div>
		  <div class="drop-text">Drag and drop files here</div>
		</div>
		<button type="submit" class="">Submit</button>
	</form>


	<script type="text/javascript">
		const dropArea = document.getElementById('drop-area');
		const droppedContent = document.getElementById('dropped-content');

		dropArea.addEventListener('dragenter', preventDefaults, false);
		dropArea.addEventListener('dragover', preventDefaults, false);
		dropArea.addEventListener('dragleave', handleDragLeave, false);
		dropArea.addEventListener('drop', handleDrop, false);

		dropArea.addEventListener('dragenter', highlight, false);
		dropArea.addEventListener('dragover', highlight, false);
		dropArea.addEventListener('dragleave', unhighlight, false);
		dropArea.addEventListener('drop', unhighlight, false);
		dropArea.addEventListener('click', openFileDialog, false);

		function preventDefaults(event) {
		  event.preventDefault();
		  event.stopPropagation();
		}

		function highlight() {
		  dropArea.classList.add('highlight');
		  dropArea.innerHTML = `
		  <div class="drop-icon">
		    <i class="fa-light fa-file-upload"></i>
		  </div>
		  <div class="drop-text">Drop files</div>
		  `; // Add this line
		}

		function unhighlight() {
		  dropArea.classList.remove('highlight');
		  dropArea.innerHTML = `
		  <div class="drop-icon">
		    <i class="fa-light fa-file-upload"></i>
		  </div>
		  <div class="drop-text">Drag and drop files here</div>
		  `;
		}

		function handleDragLeave(event) {
		  if (event.relatedTarget !== null) {
		    return;
		  }
		  unhighlight();
		}

		function handleDrop(event) {
		  event.preventDefault();
		  const file = event.dataTransfer.files[0];
		  const reader = new FileReader();

		  reader.readAsText(file);
		  reader.onload = function () {
		    droppedContent.value = reader.result;
		  };

		  unhighlight();
		}

		function openFileDialog(event) {
		  const fileInput = document.createElement('input');
		  fileInput.type = 'file';
		  fileInput.accept = 'text/plain';

		  fileInput.addEventListener('change', handleFileSelect, false);

		  fileInput.click();
		}

		function handleFileSelect(event) {
		  const file = event.target.files[0];
		  const reader = new FileReader();

		  reader.readAsText(file);
		  reader.onload = function() {
		    droppedContent.value = reader.result;
		  };
		}

		const chars = document.getElementById('chars');

		droppedContent.addEventListener('input', () => {
		  chars.innerHTML = `${droppedContent.value.length}/5000`;
		});

		const outerDot = document.getElementById('outer-dot');
		const dot = document.getElementById('dot');
		let isSpellcheck = true;

		outerDot.addEventListener('click', () => {
		  isSpellcheck = !isSpellcheck;
		  droppedContent.focus();
		  
		  if (!isSpellcheck) {
		    dot.style.transform = 'none';
		    outerDot.style.backgroundColor = '#444';
		    droppedContent.spellcheck = false;
		  } else {
		    dot.removeAttribute('style');
		    outerDot.removeAttribute('style');
		    droppedContent.spellcheck = true;
		  }
		});
	</script>
</body>
</html>