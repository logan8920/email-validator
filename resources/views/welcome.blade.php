<!DOCTYPE html>
<html>
<head>
    <title>File Upload and Batch Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.4.0/css/all.css">
    <link rel="stylesheet" type="text/css" href="{{asset('css/toastr.min.css')}}">
    <style type="text/css">
        @import url('https://fonts.googleapis.com/css2?family=Ubuntu:wght@400&display=swap');

        * {
          font-family: 'Ubuntu', sans-serif;
        }

        body {
          background-color: #222;
          color: #fff;
          margin: 20px;
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
          margin-bottom: 20px;
        }

        .drop-text {
          font-size: 18px;
          color: #888;
        }

        .drop-icon i {
          color: #888;
          font-size: 4.5rem;
        }

        .highlight {
          background-color: #444 !important;
          border-color: #007bff !important;
        }

        .custom-file-label::after {
          content: 'Browse';
        }
    </style>
    <style>
    /* Styles for the loader */
    .loader {
      display: none; /* Hidden by default */
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 1000;
      justify-content: center;
      align-items: center;
    }

    /* Loader animation */
    .loader::after {
      content: '';
      display: block;
      width: 50px;
      height: 50px;
      border: 5px solid #fff;
      border-top-color: transparent;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }

    /* Loader visible when .loading class is applied to the body */
    body.loading .loader {
      display: flex;
    }

    /* Keyframes for the spinning animation */
    @keyframes spin {
      0% {
        transform: rotate(0deg);
      }
      100% {
        transform: rotate(360deg);
      }
    }
  </style>
</head>
<body>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10 col-sm-12">
                <div id="drop-area" class="mb-3">
                    <div class="drop-icon">
                        <i class="fa-light fa-file-upload"></i>
                    </div>
                    <div class="drop-text">Drag and drop files here or click to upload</div>
                </div>

                <form method="POST" action="/upload" id="regFormss" enctype="multipart/form-data" class="mb-3">
                    @csrf
                    <div class="custom-file mb-3">
                        <input type="file" class="custom-file-input" id="excel" name="excel">
                        <label class="custom-file-label" for="excel">Choose file</label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Submit</button>
                </form>

                @if(session('success'))
                    <div class="alert alert-success" role="alert">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="table-responsive">
                  <div class="progress mb-3 d-none">
                    <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                  </div>
                    <table class="table table-dark table-striped">
                        <thead>
                            <tr>
                                <th scope="col">Sno.</th>
                                <th scope="col">File Name</th>
                                <th scope="col">Start</th>
                                <th scope="col">End</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($batch_ids as $key => $batch)
                                <tr progress="{{($batch->status == 0) ? 'true' : 'false'}}" progress-id="{{$batch->id}}">
                                    <th scope="row">{{ $key + 1 }}</th>
                                    <td>{{ $batch->file_name }}</td>
                                    <td>{{ $batch->created_at }}</td>
                                    <td>{{ $batch->updated_at }}</td>
                                    <td>{{ $batch->status == '0' ? 'pending' : ($batch->status == '1' ? 'Completed' : 'Failed') }}</td>
                                    <td><a href="{{ route('download.batch', $batch->id) }}" class="btn btn-success btn-sm" download>Download</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="{{asset('js/jquery-3.7.1.min.js')}}"></script>
    <script src="{{asset('js/jquery.validate.min.js')}}"></script>
    <script src="{{asset('js/sweetalert2@11.js')}}"></script>
    <script src="{{asset('js/toastr.min.js')}}"></script>
    <script src="{{asset('js/form.js')}}"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <script type="text/javascript">
        const dropArea = document.getElementById('drop-area');
        const fileInput = document.getElementById('excel');
        const fileLabel = document.querySelector('.custom-file-label');

        dropArea.addEventListener('dragenter', preventDefaults, false);
        dropArea.addEventListener('dragover', preventDefaults, false);
        dropArea.addEventListener('dragleave', handleDragLeave, false);
        dropArea.addEventListener('drop', handleDrop, false);

        dropArea.addEventListener('dragenter', highlight, false);
        dropArea.addEventListener('dragover', highlight, false);
        dropArea.addEventListener('dragleave', unhighlight, false);
        dropArea.addEventListener('drop', unhighlight, false);
        dropArea.addEventListener('click', () => fileInput.click(), false);

        fileInput.addEventListener('change', handleFileSelect, false);

        function preventDefaults(event) {
          event.preventDefault();
          event.stopPropagation();
        }

        function highlight() {
          dropArea.classList.add('highlight');
        }

        function unhighlight() {
          dropArea.classList.remove('highlight');
        }

        function handleDragLeave(event) {
          if (event.relatedTarget !== null) {
            return;
          }
          unhighlight();
        }

        function handleDrop(event) {
          const dt = event.dataTransfer;
          const files = dt.files;

          fileInput.files = files;
          updateFileName(files[0].name);

          unhighlight();
        }

        function handleFileSelect(event) {
          const file = event.target.files[0];
          updateFileName(file.name);
        }

        function updateFileName(name) {
          fileLabel.textContent = name;
        }
    </script>
  <script type="text/javascript">
  let intervalId = {};

  $(document).ready(function() {
    /*$('[progress="true"]').each(function() {
      intervalId[`${this.getAttribute('progress-id')}_process`] = setInterval(() => updateProgressBar(this.getAttribute('progress-id'), this), 2000);
    });*/
  });

  function updateProgressBar(id, tr) {

    const progressBarContainer = $('.progress-bar').parent();
    progressBarContainer.removeClass('d-none');
    const progressBar = $('.progress-bar');
    // console.log(progressBar)
    fetch(`/update-progress/${id}`)
      .then(res => res.json())
      .then(data => {
        console.log(data);
        if (data.success) {
          progressBar.css('width', data.width).text(`${data.width}`);
          console.log(progressBar);
          tr.querySelector('td:nth-child(5)').textContent = data.status;
          
          if (data.status === 'Completed') {
            clearInterval(intervalId[`${id}_process`]);
          }
        } else if (data.error) {
          alert('Please reload the page. Error: ' + data.error);
        }
      })
      .catch(error => {
        alert('Please reload the page. Error: ' + error);
      });
  }
</script>


</body>
</html>