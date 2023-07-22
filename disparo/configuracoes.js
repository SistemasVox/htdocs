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
                newRow.append('<td>' + log.mensagem + '</td>');

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

// Função para buscar as configurações do servidor
function buscarConfiguracoes() {
    $.ajax({
        url: 'buscar_configuracoes.php',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            // Atualiza os campos do formulário com os valores recuperados
            $('#quantidade_por_dia').val(data.quantidade_por_dia);
            $('#hora_inicio').val(data.hora_inicio);
            $('#hora_fim').val(data.hora_fim);
            $('#intervalo_minimo').val(data.intervalo_minimo);
        }
    });
}

// Função para salvar as configurações
function salvarConfiguracoes() {
    // Obter os valores do formulário
    var quantidadePorDia = $('#quantidade_por_dia').val();
    var horaInicio = $('#hora_inicio').val();
    var horaFim = $('#hora_fim').val();
    var intervaloMinimo = $('#intervalo_minimo').val();

    // Criar um objeto com as configurações
    var configuracoes = {
        quantidade_por_dia: quantidadePorDia,
        hora_inicio: horaInicio,
        hora_fim: horaFim,
        intervalo_minimo: intervaloMinimo
    };

    // Construir o conteúdo HTML com as informações das configurações
    var htmlContent = `
    <p>Tem certeza de que deseja salvar as configurações?</p>
    <p>As seguintes configurações serão salvas:</p>
    <ul>
        <li>Quantidade por dia: <span style="color: blue">${quantidadePorDia}</span></li>
        <li>Hora de início: <span style="color: blue">${horaInicio}</span></li>
        <li>Hora de fim: <span style="color: blue">${horaFim}</span></li>
        <li>Intervalo mínimo: <span style="color: blue">${intervaloMinimo}</span></li>
    </ul>
`;


    // Exibir o alerta de confirmação com as informações das configurações
    Swal.fire({
        title: 'Confirmar',
        html: htmlContent,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sim',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Enviar as configurações para o servidor
            $.ajax({
                url: 'buscar_configuracoes.php',
                type: 'POST',
                dataType: 'json',
                data: JSON.stringify(configuracoes),
                contentType: 'application/json',
                success: function(response) {
                    if (response.hasOwnProperty('success')) {
                        Swal.fire('Sucesso', 'Configurações salvas com sucesso!', 'success');
                        // Recarregar as configurações após o salvamento
                        buscarConfiguracoes();
                    } else if (response.hasOwnProperty('error')) {
                        Swal.fire('Erro', 'Erro ao salvar as configurações: ' + response.error, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire('Erro', 'Erro ao enviar as configurações: ' + error, 'error');
                }
            });

            // Limpar campos após o salvamento
            $('#form-configuracoes input[type="number"]').val('');
            $('#form-configuracoes select').val('');
            $('#form-configuracoes input[type="text"]').val('');
            $('#form-configuracoes textarea').val('');
            $('#form-configuracoes input[type="checkbox"]').prop('checked', false);
        }
    });
}

// Função para rebobinar a data de envio
function rebobinarDataEnvio() {
    // Obter as informações do formulário
    var horaInicio = $('#hora_inicio').val();
    var horaFim = $('#hora_fim').val();
    var intervalominimo = $('#intervalo_minimo').val();

    // Realizar a requisição AJAX para obter a data do servidor
    $.ajax({
        url: 'buscar_logs.php',
        type: 'GET',
        dataType: 'json',
        data: {
            action: 'data_servidor'
        },
        success: function(response) {
            if (response.success) {
                var dataServidor = response.message; // Obter a data do servidor da resposta

                // Formatar a data no padrão brasileiro
                var dataHoraFormatada = formatarDataHora(dataServidor);

                // Exibir o alerta de confirmação com a data do servidor formatada
                Swal.fire({
                    title: 'Confirmar',
                    text: 'Tem certeza de que deseja rebobinar as datas de envio?\n\nData do Servidor: ' + dataHoraFormatada,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sim',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Realizar a requisição AJAX para rebobinar
                        $.ajax({
                            url: 'buscar_logs.php',
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'rebobinar',
                                hora_inicio: horaInicio,
                                hora_fim: horaFim,
                                intervalo_minimo: intervalominimo
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire('Sucesso', response.message, 'success').then(() => {
                                        // Atualizar a página após rebobinar
                                        atualizarTabela();
                                    });
                                } else {
                                    Swal.fire('Erro', 'Erro ao rebobinar: ' + response.message, 'error');
                                }
                            },
                            error: function(xhr, status, error) {
                                console.log(xhr.responseText); // Exibir resposta em formato errado no console
                                Swal.fire('Erro', 'Erro ao rebobinar: ' + error, 'error');
                            }
                        });
                    }
                });
            } else {
                Swal.fire('Erro', 'Erro ao obter a data do servidor: ' + response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            console.log(xhr.responseText); // Exibir resposta em formato errado no console
            Swal.fire('Erro', 'Erro ao obter a data do servidor: ' + error, 'error');
        }
    });
}

// Função para avançar a data de envio
function avancarDataEnvio() {
    // Obter as informações do formulário
    var horaInicio = $('#hora_inicio').val();
    var horaFim = $('#hora_fim').val();
    var intervalominimo = $('#intervalo_minimo').val();

    // Realizar a requisição AJAX para obter a data do servidor
    $.ajax({
        url: 'buscar_logs.php',
        type: 'GET',
        dataType: 'json',
        data: {
            action: 'data_servidor'
        },
        success: function(response) {
            if (response.success) {
                var dataServidor = response.message; // Obter a data do servidor da resposta

                // Formatar a data no padrão brasileiro
                var dataHoraFormatada = formatarDataHora(dataServidor);

                // Exibir o alerta de confirmação com a data do servidor formatada
                Swal.fire({
                    title: 'Confirmar',
                    text: 'Tem certeza de que deseja avançar as datas de envio?\n\nData do Servidor: ' + dataHoraFormatada,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sim',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Realizar a requisição AJAX para avançar
                        $.ajax({
                            url: 'buscar_logs.php',
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'avancar',
                                hora_inicio: horaInicio,
                                hora_fim: horaFim,
                                intervalo_minimo: intervalominimo
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire('Sucesso', response.message, 'success').then(() => {
                                        // Atualizar a página após o avanço
                                        atualizarTabela();
                                    });
                                } else {
                                    Swal.fire('Erro', 'Erro ao avançar: ' + response.message, 'error');
                                }
                            },
                            error: function(xhr, status, error) {
                                console.log(xhr.responseText); // Exibir resposta em formato errado no console
                                Swal.fire('Erro', 'Erro ao avançar: ' + error, 'error');
                            }
                        });
                    }
                });
            } else {
                Swal.fire('Erro', 'Erro ao obter a data do servidor: ' + response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            console.log(xhr.responseText); // Exibir resposta em formato errado no console
            Swal.fire('Erro', 'Erro ao obter a data do servidor: ' + error, 'error');
        }
    });
}

function formatarDataHora(dataHora) {
    var partes = dataHora.split(' ');
    var data = partes[0].split('-').reverse().join('/');
    var hora = partes[1].substr(0, 8);
    return data + ' ' + hora;
}

function adicionarNovosClientes() {
    // Exibir o alerta de confirmação com SweetAlert2
    Swal.fire({
        title: 'Confirmar',
        text: 'Tem certeza de que deseja adicionar novos clientes?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sim',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Realizar a requisição AJAX para adicionar novos clientes
            $.ajax({
                url: 'buscar_logs.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'add_clientes',
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Sucesso', response.message, 'success').then(() => {
                            // Atualizar a página após a adição de clientes
                            atualizarTabela();
                        });
                    } else {
                        Swal.fire('Erro', 'Erro ao adicionar clientes: ' + response.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.log(xhr.responseText);
                    Swal.fire('Erro', 'Erro ao adicionar clientes: ' + error, 'error');
                }
            });
        }
    });
}

function atacar() {
    // Realizar a requisição AJAX para obter os serviços
    $.ajax({
        url: 'buscar_logs.php',
        type: 'GET',
        data: {
            action: 'servicos'
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var servicos = response.message;

                // Gerar o HTML dos serviços dinamicamente
                var servicesHtml = '';
                for (var i = 0; i < servicos.length; i++) {
                    servicesHtml += '<label class="form-check-label">';
                    servicesHtml += '<input type="checkbox" name="servicos[]" value="' + servicos[i].id + '">' + servicos[i].descricao;
                    servicesHtml += '</label><br>';
                }

                // Exibir o alerta de confirmação com os serviços
                Swal.fire({
                    title: 'Confirmar',
                    html: '<p>Tem certeza de que deseja realizar o ataque?</p>' + servicesHtml,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sim',
                    cancelButtonText: 'Cancelar',
                    preConfirm: () => {
                        const selectedOptions = document.querySelectorAll('input[name="servicos[]"]:checked');
                        const selectedServices = Array.from(selectedOptions).map(option => option.value);

                        if (selectedServices.length > 0) {
                            return selectedServices;
                        } else {
                            Swal.showValidationMessage('Selecione pelo menos um serviço para o ataque.');
                            return false;
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Realizar a requisição AJAX para executar o ataque com os serviços selecionados
                        $.ajax({
                            url: 'buscar_logs.php',
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'atacar',
                                servicos: result.value
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire('Sucesso', response.message, 'success').then(() => {
                                        // Resto da lógica após o sucesso do ataque
                                    });
                                } else {
                                    Swal.fire('Erro', 'Erro ao realizar o ataque: ' + response.message, 'error');
                                }
                            },
                            error: function(xhr, status, error) {
                                console.log(xhr.responseText);
                                Swal.fire('Erro', 'Erro ao realizar o ataque: ' + error, 'error');
                            }
                        });
                    }
                });
            } else {
                Swal.fire('Erro', 'Erro ao buscar os serviços: ' + response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            console.log(xhr.responseText);
            Swal.fire('Erro', 'Erro ao buscar os serviços: ' + error, 'error');
        }
    });
}



$(document).ready(function() {
    // Chama a função para buscar as configurações
    buscarConfiguracoes();

    // Lógica para salvar as configurações
    $('#form-configuracoes').submit(function(e) {
        e.preventDefault();
        salvarConfiguracoes();
    });

    // Lógica para rebobinar a data de envio
    $('.rebobinar-btn').click(function() {
        rebobinarDataEnvio();
    });

    // Lógica para avançar a data de envio
    $('.avancar-btn').click(function() {
        avancarDataEnvio();
    });

    // Lógica para acionar a função adicionarNovosClientes ao clicar no botão "Novos clientes"
    $('.incluir-btn[name="novos"]').click(adicionarNovosClientes);

    // Lógica para acionar a função atacar ao clicar no botão "Atacar"
    $('.atacar-btn').click(function(event) {
        event.stopPropagation(); // Evita a propagação do evento de clique
        atacar();
    });
});
