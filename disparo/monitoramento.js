// Função para atualizar a tabela de monitoramento
function atualizarTabela() {
    $.ajax({
        url: 'buscar_logs.php',
        type: 'GET',
        data: { action: 'logs' },
        dataType: 'json',
        success: function(data) {
            // Limpa as linhas da tabela
            $('#tabela-monitoramento tbody').empty();

            // Percorre os logs e adiciona as linhas na tabela
            for (var i = 0; i < data.logs.length; i++) {
                var log = data.logs[i];
                var newRow = $('<tr>');
                newRow.append('<td>' + log.id + '</td>');
                newRow.append('<td>' + log.hora + '</td>');
                newRow.append('<td>' + log.nome + '</td>');
                newRow.append('<td>' + log.cnpj + '</td>');
                newRow.append('<td>' + log.numero + '</td>');

                // Verifica se há uma mensagem associada a esse log
                var mensagem = log.id_mensagem !== null ? log.id_mensagem : 'Sem mensagem';
                newRow.append('<td>' + mensagem + '</td>');

                // Modifica o campo "enviado" para exibir "Sim" ou "Não"
                var enviado = log.enviado === '1' || log.enviado === 1 ? 'Sim' : 'Não';
                newRow.append('<td>' + enviado + '</td>');

                // Define a cor da célula do campo "enviado" baseado no valor "Sim"
                if (enviado === 'Sim') {
                    newRow.find('td:last-child').addClass('celula-sim');
                }

                $('#tabela-monitoramento tbody').append(newRow);
            }
        }
    });
}



// Função para atualizar as informações do status do banco de dados
function atualizarStatusBanco() {
    $.ajax({
        url: 'buscar_logs.php',
        type: 'GET',
        data: { action: 'status' },
        dataType: 'json',
        success: function(data) {
            /* console.log("Recebendo dados do servidor:", data); */
            var $infoBanco = $('#info-banco');
            $infoBanco.empty(); // Limpa o conteúdo atual da div

            // Cria os elementos HTML para exibir as informações do status
            var $titulo = $('<h2>').text('Status do Banco de Dados');
            $infoBanco.append($titulo);

            if (data && data.status && data.status.length > 0) {
                var $ul = $('<ul>');
                for (var i = 0; i < data.status.length; i++) {
                    var $li = $('<li>').text(data.status[i] + ".");
                    $ul.append($li);
                }
                $infoBanco.append($ul);
            } else {
                var $p = $('<p>').text('Não há informações de status disponíveis no momento.');
                $infoBanco.append($p);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            /* console.log("Erro na requisição AJAX:", textStatus, errorThrown); */
        }
    });
}

$(document).ready(function() {
    // Atualiza a tabela e o status do banco de dados inicialmente
    atualizarTabela();
    atualizarStatusBanco();

    // Atualiza a tabela e o status do banco de dados a cada 5 segundos
    setInterval(function() {
        atualizarTabela();
        atualizarStatusBanco();
    }, 5000);
});
