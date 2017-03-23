//Controla la eliminacion pror AJAX
$(document).ready(function(){
    //Seleccionamos el registro a eliminar
    $('.btn-delete').click(function(e){
        //evita que la pagina se recarge al hacer click en el boton
        e.preventDefault();
        //Obtenemos el padre del elemento en que estamos que debe ser (tr).
        var row = $(this).parents('tr');
        //Obtenemos el id del registro.
        var id = row.data('id');
        
        //alert(id);
        //Otenemos el formulario de eliminacion creado
        var form = $('#form-delete');
        //Otener la ruta con el Id correspondiente donde esta el comodin
        var url = form.attr('action').replace(':USER_ID', id);
        //Serializamos el formulario para su correcto envio.
        var data = form.serialize();
        //alert(data);
        //Enviamos el formulario al controlador para que sea procesado
        //Antes vamos a confirmar con bootbox
        bootbox.confirm(message, function(res){
            if(res == true)
            {
            // Se muestra el preloader    
                $('#delete-progress').removeClass('hidden');
            //Obtenemos los datos del controlador y segun ello mostramos.
                $.post(url, data, function(result){
                    // Se oculta el preloader
                    $('#delete-progress').addClass('hidden');
                    
                    if(result.removed == 1)
                    {
                        row.fadeOut();
                    //muestra el mensaje de exito escondido
                        $('#message').removeClass('hidden');
                    //mustra el texto del mensaje
                        $('#user-message').text(result.message);
                    //extrae el valor de la cant de usuarios  
                        var totalUsers = $('#totalUsers').text();
                    //valida si es numerico..!!!..?????
                        if($.isNumeric(totalUsers))
                        {
                            $('#total').text(totalUsers - 1);
                        }
                        else
                        {
                            $('#total').text(result.countUsers);
                        }
                    }
                    else
                    {
                        $('#message-danger').removeClass('hidden');
                        
                        $('#user-message-danger').text(result.message);
                    }
                }).fail(function(){
                    alert('error');
                    row.show();
                });
            }
        });
    });
});