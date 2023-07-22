$(document).ready(function() {
    // Lógica para o envio de mensagens
   $('#form-envio').submit(function(e) {
        e.preventDefault();
        
        // Lógica de envio de mensagens
        
        // Limpar campos após o envio
        $('#form-envio textarea').val('');
        $('#form-envio input[type="text"]').val('');
        $('#form-envio input[type="datetime-local"]').val('');
    });
});
