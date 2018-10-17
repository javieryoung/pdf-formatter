<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet" type="text/css">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
        <script src="{{ asset('/js/select-areas.js') }}"></script>


        <!-- Styles -->
        <style>

        .select-areas-overlay {
        	background-color: #000;
        	overflow: hidden;
        	position: absolute;
        }
        .select-areas-outline {
        	overflow: hidden;
        }

        .select-areas-resize-handler {
        	background-color: #000;
        	border: 1px #fff solid;
        	height: 8px;
        	width: 8px;
        	overflow: hidden;
        }

        .select-areas-delete-area {
        	background: url('{{ asset("/bt-delete.png") }}');
        	cursor: pointer;
        	height: 16px;
        	width: 16px;
        }
        .delete-area {
        	position: absolute;
        	cursor: pointer;
        	padding: 5px;
        }
        </style>
    </head>
    <body>
      <div style="float:left; width:600px;">
        <img style="border: 1px solid #ccc; width: 600px;" id="img">
      </div>

      <div style="float:left; margin-left: 10px;">
        Año: <input type="text" placeholder="Año" id="year" value="<?php echo $data['year']; ?>"><br><br>
        Título: <input type="text" placeholder="Título" id="title" value="<?php echo $data['title']; ?>"> <span style="color: red; cursor: pointer;" id="deleteTitle">borrar</span><br><br>
        <button id="nextPage"> Siguiente pagina </button>
        <button id="deletePage" style="color: red;"> Eliminar pagina </button>
      </div>
      <div class="debug">
        <?php if (isset($data['debug']) && strlen($data['debug']) > 0) var_dump($data['debug']); ?>
      </div>
      <script>
      var images = JSON.parse('<?php echo $data['images']; ?>');
      loadPage(0);
      var currentPage;
      var edits = {
        areas: {},
        deleted: [],
        path: '<?php echo $data['path']; ?>'
      }
      function loadPage(number) {
        $('#img').selectAreas('destroy');
        currentPage = number;
        $("#img").attr('src', images[number]);

        var height = 0;
        setTimeout(function() {
          height = $("#img").height();
          $('#img').selectAreas('add',
          {
            id: 2,
            x: 0,
            y: height -30,
            width: '500',
            height: '30'
          })
        },100)
        $("#img").selectAreas({
          overlayOpacity: 0.1,
          allowDelete: true,
          width: 600,
          areas: [{
            id: 1,
            x: 0,
            y: 0,
            width: '600',
            height: '30'
          },
        ]
        });
      }

      function finishPage() {
        var width = 600;
        var height = $('#img').height();
        edits.areas[currentPage] = [];
        for (let a of ($('#img').selectAreas('areas'))) {
          let pwidth = (a.width * 100) / width;
          let pheight = (a.height * 100) / height;
          let px = (a.x * 100) / width;
          let py = (a.y * 100) / height;
          edits.areas[currentPage].push({
            width: pwidth.toFixed(4),
            height: pheight.toFixed(4),
            x: px.toFixed(4),
            y: py.toFixed(4)
          });

        }
      }


      var nextClicked = function() {
        finishPage();
        if (currentPage == images.length-1){
          edits.title = $('#title').val();
          edits.year = $('#year').val();
          if (edits.title == '')
            return ;
          $.ajax({
            url: '/api/save_pdf',
            type: 'post',
            data: JSON.stringify(edits),
            dataType: 'json',
            contentType: 'application/json',
            success: function (data) {
              location.reload();
            },
            error: function (data) {
              location.reload();
            }
          })
        } else
          loadPage(currentPage+1);
        if (currentPage == images.length-1)
          $('#nextPage').html('Finalizar');

      }

      var deleteClicked = function() {
        edits.deleted.push(currentPage);
        loadPage(currentPage+1);
      }

      $( "body" ).keypress(function( event ) {
        if ( event.which == 110 ) {
           nextClicked();
        }
        if ( event.which == 101 ) {
           deleteClicked();
        }
      });
      $('#nextPage').on('click', nextClicked)
      $('#deletePage').on('click', deleteClicked);
      $('#deleteTitle').on('click', function() {
        $('#title').val('');
      })
      </script>
    </body>
</html>
