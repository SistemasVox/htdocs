// Função para atualizar a tabela de monitoramento
function atualizarTabela() {
	$.ajax({
		url: 'buscar_logs.php',
		type: 'GET',
		data: {
			action: 'logs'
		},
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
// Função para gerenciar (avançar ou rebobinar) a data de envio
function gerenciarDataEnvio(acao) {
	// Obter os valores dos campos de entrada do formulário
	var horaInicio = $('#hora_inicio').val();
	var horaFim = $('#hora_fim').val();
	var intervaloMinimo = $('#intervalo_minimo').val();
	var quantidadePorDia = $('#quantidade_por_dia').val();
	// Primeira chamada AJAX para obter a data atual do servidor
	$.ajax({
		url: 'buscar_logs.php',
		type: 'GET',
		dataType: 'json',
		data: {
			action: 'data_servidor'
		},
		success: function(response) {
			// Se a chamada foi bem-sucedida
			if (response.success) {
				// Obter a data do servidor da resposta
				var dataServidor = response.message;
				// Formatar a data no padrão brasileiro
				var dataHoraFormatada = formatarDataHora(dataServidor);
				// Formatando a ação para exibição
				var acaoFormatada = acao === "avancar" ? "avançar" : "rebobinar";
				// Preparar o texto da confirmação
				var confirmacaoTexto = `Tem certeza de que deseja ${acaoFormatada} as datas de envio?\n\nData do Servidor: ${dataHoraFormatada}`;
				// Exibir o alerta de confirmação
				Swal.fire({
					title: 'Confirmar',
					text: confirmacaoTexto,
					icon: 'warning',
					showCancelButton: true,
					confirmButtonColor: '#3085d6',
					cancelButtonColor: '#d33',
					confirmButtonText: 'Sim',
					cancelButtonText: 'Cancelar'
				}).then((result) => {
					// Se o usuário confirmou a ação
					if (result.isConfirmed) {
						// Segunda chamada AJAX para realizar a ação (avançar/rebobinar)
						$.ajax({
							url: 'buscar_logs.php',
							type: 'POST',
							dataType: 'json',
							data: {
								action: acao,
								hora_inicio: horaInicio,
								hora_fim: horaFim,
								intervalo_minimo: intervaloMinimo,
								quantidade_por_dia: quantidadePorDia
							},
							success: function(response) {
								// Se a ação foi bem-sucedida
								if (response.success) {
									// Exibir mensagem de sucesso e atualizar a tabela
									Swal.fire('Sucesso', response.message, 'success').then(() => {
										atualizarTabela();
									});
								} else {
									// Se houve um erro, exibir a mensagem de erro
									Swal.fire('Erro', 'Erro ao ' + acaoFormatada + ': ' + response.message, 'error');
								}
							},
							error: function(xhr, status, error) {
								// Exibir no console a resposta da chamada AJAX
								console.log(xhr.responseText);
								// Exibir a mensagem de erro
								Swal.fire('Erro', 'Erro ao ' + acaoFormatada + ': ' + error, 'error');
							}
						});
					}
				});
			} else {
				// Se houve um erro ao obter a data do servidor, exibir a mensagem de erro
				Swal.fire('Erro', 'Erro ao obter a data do servidor: ' + response.message, 'error');
			}
		},
		error: function(xhr, status, error) {
			// Exibir no console a resposta da chamada AJAX
			console.log(xhr.responseText);
			// Exibir a mensagem de erro
			Swal.fire('Erro', 'Erro ao obter a data do servidor: ' + error, 'error');
		}
	});
}
// Função para formatar data e hora no padrão brasileiro (dd/mm/aaaa hh:mm:ss)
function formatarDataHora(dataHora) {
	// Divide a string de data e hora em duas partes: data e hora
	var partes = dataHora.split(' ');
	// Reverte a ordem dos componentes da data (de aaaa-mm-dd para dd-mm-aaaa) e substitui os hifens por barras
	var data = partes[0].split('-').reverse().join('/');
	// Mantém apenas as partes de hora, minuto e segundo da string de hora
	var hora = partes[1].substr(0, 8);
	// Retorna a data e hora formatadas como uma única string
	return data + ' ' + hora;
}
// Função para adicionar novos clientes à tabela
function adicionarNovosClientes() {
	// Exibir um alerta de confirmação usando SweetAlert2
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
			// Se o usuário confirmar, fazer uma requisição AJAX para adicionar novos clientes
			$.ajax({
				url: 'buscar_logs.php',
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'add_clientes',
				},
				success: function(response) {
					// Se a resposta indicar sucesso, exibir um alerta de sucesso e atualizar a tabela
					if (response.success) {
						Swal.fire('Sucesso', response.message, 'success').then(() => {
							atualizarTabela();
						});
					} else {
						// Se a resposta indicar falha, exibir um alerta de erro com a mensagem de erro
						Swal.fire('Erro', 'Erro ao adicionar clientes: ' + response.message, 'error');
					}
				},
				error: function(xhr, status, error) {
					// Se houver um erro na requisição AJAX, exibir a resposta em texto no console e um alerta de erro
					console.log(xhr.responseText);
					Swal.fire('Erro', 'Erro ao adicionar clientes: ' + error, 'error');
				}
			});
		}
	});
}
// Função para realizar o ataque
function atacar() {
	// Requisição AJAX para buscar os serviços
	$.ajax({
		url: 'buscar_logs.php',
		type: 'GET',
		data: {
			action: 'servicos'
		},
		dataType: 'json',
		success: function(response) {
			// Se a resposta for bem-sucedida
			if (response.success) {
				var servicos = response.message;
				// Gerar HTML para exibir as opções de serviço
				var servicesHtml = '';
				for (var i = 0; i < servicos.length; i++) {
					servicesHtml += '<label class="form-check-label">';
					servicesHtml += '<input type="checkbox" name="servicos[]" value="' + servicos[i].id + '">' + servicos[i].descricao;
					servicesHtml += '</label><br>';
				}
				// Exibir um prompt de confirmação para o usuário selecionar os serviços para o ataque
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
						// Coletar os serviços selecionados pelo usuário
						const selectedOptions = document.querySelectorAll('input[name="servicos[]"]:checked');
						const selectedServices = Array.from(selectedOptions).map(option => option.value);
						if (selectedServices.length > 0) {
							return selectedServices;
						} else {
							// Se nenhum serviço for selecionado, mostrar mensagem de validação
							Swal.showValidationMessage('Selecione pelo menos um serviço para o ataque.');
							return false;
						}
					}
				}).then((result) => {
					if (result.isConfirmed) {
						// Se o usuário confirmar o ataque, realizar requisição AJAX para executar o ataque com os serviços selecionados
						$.ajax({
							url: 'buscar_logs.php',
							type: 'POST',
							dataType: 'json',
							data: {
								action: 'atacar',
								servicos: result.value
							},
							success: function(response) {
								// Se a resposta for bem-sucedida, mostrar mensagem de sucesso
								if (response.success) {
									Swal.fire('Sucesso', response.message, 'success').then(() => {
										// Aqui você pode colocar a lógica que deve ser executada após um ataque bem-sucedido
									});
								} else {
									// Se houver erro, mostrar mensagem de erro
									Swal.fire('Erro', 'Erro ao realizar o ataque: ' + response.message, 'error');
								}
							},
							error: function(xhr, status, error) {
								// Se houver um erro na requisição AJAX, mostrar mensagem de erro
								console.log(xhr.responseText);
								Swal.fire('Erro', 'Erro ao realizar o ataque: ' + error, 'error');
							}
						});
					}
				});
			} else {
				// Se houver um erro ao buscar os serviços, mostrar mensagem de erro
				Swal.fire('Erro', 'Erro ao buscar os serviços: ' + response.message, 'error');
			}
		},
		error: function(xhr, status, error) {
			// Se houver um erro na requisição AJAX, mostrar mensagem de erro
			console.log(xhr.responseText);
			Swal.fire('Erro', 'Erro ao buscar os serviços: ' + error, 'error');
		}
	});
}
// Executar quando o documento estiver totalmente carregado
$(document).ready(function() {
	// Chama a função buscarConfiguracoes para preencher os campos do formulário com as configurações existentes
	buscarConfiguracoes();
	// Adiciona um manipulador de evento ao formulário de configurações para evitar o comportamento padrão de envio e chamar a função salvarConfiguracoes
	$('#form-configuracoes').submit(function(e) {
		e.preventDefault();
		salvarConfiguracoes();
	});
	// Adiciona um manipulador de evento ao botão rebobinar para chamar a função gerenciarDataEnvio com o parâmetro "rebobinar"
	$('.rebobinar-btn').click(function() {
		gerenciarDataEnvio("rebobinar");
	});
	// Adiciona um manipulador de evento ao botão avançar para chamar a função gerenciarDataEnvio com o parâmetro "avancar"
	$('.avancar-btn').click(function() {
		gerenciarDataEnvio("avancar");
	});
	// Adiciona um manipulador de evento ao botão incluir novos clientes para chamar a função adicionarNovosClientes
	$('.incluir-btn[name="novos"]').click(adicionarNovosClientes);
	// Adiciona um manipulador de evento ao botão atacar para prevenir a propagação do evento de clique e chamar a função atacar
	$('.atacar-btn').click(function(event) {
		event.stopPropagation();
		atacar();
	});
});