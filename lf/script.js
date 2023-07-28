function generateHtml(id, label, data, comparator) {
	let uniqueData = removerDuplicata(data);
	let output = label;
	for (let i = 0; i < uniqueData.length; i++) {
		if (i > 0 && comparator(uniqueData[i], uniqueData[i - 1])) {
			output += "<span>-</span>";
		}
		output += "<span class='bola'>" + uniqueData[i] + "</span>";
	}
	document.getElementById(id).innerHTML = output;
}

function gerarRandomico() {
	fetch('generate.php?action=generate').then(response => {
		if (!response.ok) {
			throw Error('Erro na chamada da API');
		}
		return response.json();
	}).then(data => {
		/* console.log('Conteúdo do JSON:', data.jogo); */
		let jogo = data.jogo;
		let html = "";
		for (let i = 0; i < jogo.length; i++) {
			html += "<span id='bola" + (i + 1) + "' class='bola'>" + jogo[i] + "</span>";
		}
		document.getElementById("bolas").innerHTML = html;
		generateHtml("id_dez", "Dezena: ", getDezCom(jogo), compareFirstDigit);
		generateHtml("id_seq", "Sequência: ", getDezSeq(jogo), compareDifference);
		generateHtml("id_fim", "Final: ", getDezFim(jogo), compareLastDigit);
		const estatisticas = calcularEstatisticas(jogo);
		const resumoHtml = `Resumo: D0: ${estatisticas.D0}, D1: ${estatisticas.D1}, D2: ${estatisticas.D2}, SEQ: ${estatisticas.Seq}, FIM: ${estatisticas.Fim}.`;
		document.getElementById("id_res").innerText = resumoHtml;
	}).catch(error => {
		console.log('Ocorreu um erro:', error);
		Swal.fire({
			icon: 'error',
			title: 'Oops...',
			text: 'Ocorreu um erro na chamada da API. Verifique o console para mais detalhes.'
		});
	});
}
const compareFirstDigit = (a, b) => String(a).charAt(0) != String(b).charAt(0);
const compareDifference = (a, b) => Number(a) - Number(b) >= 2;
const compareLastDigit = (a, b) => String(a).charAt(String(a).length - 1) != String(b).charAt(String(b).length - 1);

function calcularEstatisticas(jogo) {
	const dezenas = jogo.map(numero => numero.toString());

	const dezena0 = dezenas.filter(numero => numero.startsWith('0')).length;
	const dezena1 = dezenas.filter(numero => numero.startsWith('1')).length;
	const dezena2 = dezenas.filter(numero => numero.startsWith('2')).length;

	const sequencias = [];
	let sequenciaAtual = [];
	for (let i = 0; i < dezenas.length - 1; i++) {
		if (parseInt(dezenas[i + 1]) - parseInt(dezenas[i]) === 1) {
			if (sequenciaAtual.length === 0) {
				sequenciaAtual.push(dezenas[i]);
			}
			sequenciaAtual.push(dezenas[i + 1]);
		} else {
			if (sequenciaAtual.length > 1) {
				sequencias.push(sequenciaAtual.join(','));
			}
			sequenciaAtual = [];
		}
	}

	const finais = dezenas.map(numero => numero.charAt(numero.length - 1));
	const finaisUnicos = new Set(finais);
	const finaisRepetidos = finais.length - finaisUnicos.size;

	return {
		D0: dezena0,
		D1: dezena1,
		D2: dezena2,
		Seq: sequencias.length,
		Fim: finaisUnicos.size,
		FimR: finaisRepetidos,
	};
}

function getDezCom(d) {
	var dezenas = {};
	for (var i = 0; i < d.length; i++) {
		var dezena = String(d[i]).substring(0, 1);
		if (!dezenas[dezena]) {
			dezenas[dezena] = [];
		}
		dezenas[dezena].push(d[i]);
	}
	var v = [];
	for (var dezena in dezenas) {
		if (dezenas[dezena].length > 1) {
			v = v.concat(dezenas[dezena]);
		}
	}
	return v;
}

function getDezSeq(d) {
	var v = [];
	for (var i = 1; i < d.length; i++) {
		if (parseInt(d[i], 10) == parseInt(d[i - 1], 10) + 1) {
			v.push(d[i - 1], d[i]);
		}
	}
	return v;
}

function getDezFim(d) {
	var fins = {};
	for (var i = 0; i < d.length; i++) {
		var fim = String(d[i]).slice(-1);
		if (!fins[fim]) {
			fins[fim] = [];
		}
		fins[fim].push(d[i]);
	}
	var v = [];
	for (var fim in fins) {
		if (fins[fim].length > 1) {
			v = v.concat(fins[fim]);
		}
	}
	return v;
}

function removerDuplicata(data) {
	return [...new Set(data)];
}

function copyClipboard() {
	var copyText = "";
	for (var i = 0; i < 15; i++) {
		if (i < 14) {
			copyText += document.getElementById("bola" + (i + 1)).innerText + " ";
		} else {
			copyText += document.getElementById("bola" + (i + 1)).innerText;
		}
	}
	if (navigator.clipboard) {
		navigator.clipboard.writeText(copyText);
		alert("Copiado: " + copyText);
	} else {
		copy(copyText);
		alert("Copiado: " + copyText);
	}
}

function copyClipboardf() {
	var copyText = "";
	console.log("Script carregado com sucesso.");
	for (var i = 0; i < 15; i++) {
		copyText += document.getElementById("bola" + (i + 1)).innerText + " ";
		if ((i + 1) % 5 === 0) {
			copyText += "\n";
		}
	}
	copyText = copyText.trim(); // Remover espaço em branco no final
	if (navigator.clipboard) {
		navigator.clipboard.writeText(copyText);
		alert("Copiado:\n" + copyText);
	} else {
		copy(copyText);
		alert("Copiado:\n" + copyText);
	}
}

function copy(text) {
	var input = document.createElement('input');
	input.setAttribute('value', text);
	document.body.appendChild(input);
	input.select();
	var result = document.execCommand('copy');
	document.body.removeChild(input);
	return result;
}
$(document).ready(function() {
	updateStatistics();
	$('#statisticsType').on('change', function() {
		updateStatistics();
	});
});
$('#statisticsType').on('change', function() {
	console.log('Option changed');
	updateStatistics();
});

// Chamar a função para preencher a seção quando a página carregar
$(document).ready(function() {
    updateDatabaseInfo();
});

window.onload = function() {
	// Nomes para os radio buttons
	/* const aiOptions = ['A.I 1', 'A.I 2', 'A.I 3']; */
	const aiOptions = ['A.I Alpha', 'A.I Beta', 'A.I Dev Build'];
	// Elemento onde os radio buttons serão inseridos
	const radioButtonContainer = document.getElementById('radiobuttons');
	aiOptions.forEach(function(name, index) {
		// Cria o elemento do radio button
		let radioButton = document.createElement('input');
		radioButton.type = 'radio';
		radioButton.name = 'aiOption';
		radioButton.id = `aiOption${index+1}`;
		radioButton.value = `option${index+1}`;
		// Cria a label para o radio button
		let label = document.createElement('label');
		label.htmlFor = radioButton.id;
		label.textContent = name;
		// Cria a div para conter o radio button e a label
		let div = document.createElement('div');
		div.classList.add('form-check', 'mr-2');
		div.appendChild(radioButton);
		div.appendChild(label);
		// Adiciona a div ao container
		radioButtonContainer.appendChild(div);
	});
}

function updateStatistics(start = null, end = null) {
	const type = $('#statisticsType').val();
	const title = type === 'par' ? 'Pares' : type === 'impar' ? 'Ímpares' : 'Todos';
	$('#statisticsTitle').text(title);
	$.ajax({
		url: 'http://localhost/lf/api.php',
		method: 'GET',
		data: {
			action: 'even_odd_stats',
			type: type,
			start: start,
			end: end
		},
		dataType: 'json',
		success: function(data) {
			let dataArray = Object.values(data);
			if (dataArray.length > 0) {
				populateStatisticsTable(dataArray, 'statisticsTable');
			} else {
				Swal.fire({
					icon: 'error',
					title: 'Oops...',
					text: 'Erro no servidor: Sem dados retornados.',
				});
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			Swal.fire({
				icon: 'error',
				title: 'Oops...',
				text: 'Erro na solicitação: ' + textStatus + '. ' + errorThrown,
			});
		}
	});
}

function populateStatisticsTable(stats, tableId) {
	let table = $("#" + tableId + " tbody");
	table.empty();
	for (let stat of stats) {
		let row = $("<tr></tr>");
		let numberCell = $("<td></td>").text(stat.number.toString().padStart(2, '0')); // Add leading zero to numbers
		let frequencyCell = $("<td></td>").text(stat.frequency);
		row.append(numberCell, frequencyCell);
		table.append(row);
	}
}

// Função para preencher a seção "Informação do Banco de Dados"
function updateDatabaseInfo() {
    $.ajax({
        url: 'api.php?action=get_total_games',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            $('#totalJogos').text(data.total);
        },
        error: function() {
            $('#totalJogos').text('Erro ao obter o total de jogos no banco de dados.');
        }
    });

    $.ajax({
        url: 'api.php?action=get_last_concurso',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            $('#ultimoConcurso').text('Concurso: ' + data.concurso + ', Data: ' + data.data_concurso);
        },
        error: function() {
            $('#ultimoConcurso').text('Erro ao obter o último concurso no banco de dados.');
        }
    });

    $.ajax({
        url: 'api.php?action=get_most_frequent_numbers',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            let html = '';
            for (let numero in data) {
                html += '<li>' + numero + ' - Frequência: ' + data[numero] + '</li>';
            }
            $('#mostFrequentNumbers').html(html);
        },
        error: function() {
            $('#mostFrequentNumbers').html('Erro ao obter os números mais frequentes no banco de dados.');
        }
    });

    $.ajax({
        url: 'api.php?action=get_least_frequent_numbers',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            // Ordenar o objeto data por frequência em ordem crescente
            const sortedData = Object.keys(data).sort((a, b) => data[a] - data[b]);

            let html = '';
            for (let numero of sortedData) {
                html += '<li>' + numero + ' - Frequência: ' + data[numero] + '</li>';
            }
            $('#leastFrequentNumbers').html(html);
        },
        error: function() {
            $('#leastFrequentNumbers').html('Erro ao obter os números menos frequentes no banco de dados.');
        }
    });

    $.ajax({
        url: 'api.php?action=get_number_range',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            $('#minRange').text(data.min);
            $('#maxRange').text(data.max);
            $('#averageRange').text(data.average);
        },
        error: function() {
            $('#minRange').text('Erro ao obter o intervalo mínimo no banco de dados.');
            $('#maxRange').text('Erro ao obter o intervalo máximo no banco de dados.');
            $('#averageRange').text('Erro ao obter a média do intervalo no banco de dados.');
        }
    });

	// Obtendo as estatísticas de todos os jogos
	$.ajax({
		url: 'api.php?action=calcularEstatisticasDeTodosOsJogos',
		method: 'GET',
		dataType: 'json',
		success: function(data) {
			// Exibindo as estatísticas no HTML
			$('#d0MaisComum').html(formatObjectByFrequency(data.D0MaisComum));
			$('#d1MaisComum').html(formatObjectByFrequency(data.D1MaisComum));
			$('#d2MaisComum').html(formatObjectByFrequency(data.D2MaisComum));
			$('#sequenciaMaisComum').html(formatObjectByFrequency(data.SequenciaMaisComum));
			$('#FinaisRepetidosMaisComuns').html(formatObjectByFrequency(data.FinaisRepetidosMaisComuns));
			$('#finaisMaisComuns').html(formatObjectByFrequency(data.FinaisMaisComuns));
		},
		error: function() {
			// Tratamento de erro
			$('#d0MaisComum').text('Erro ao obter as estatísticas de D0.');
			$('#d1MaisComum').text('Erro ao obter as estatísticas de D1.');
			$('#d2MaisComum').text('Erro ao obter as estatísticas de D2.');
			$('#sequenciaMaisComum').text('Erro ao obter as estatísticas de Sequência.');
			$('#finaisMaisComuns').text('Erro ao obter as estatísticas de Finais.');
		}
	});



}

// Função auxiliar para formatar um objeto ordenado por frequência em HTML
function formatObjectByFrequency(obj) {
    // Converter o objeto em um array de pares chave-valor
    const entries = Object.entries(obj);

    // Ordenar o array com base na frequência (valor) em ordem decrescente
    entries.sort((a, b) => b[1] - a[1]);

    // Construir a string HTML
    let html = '<ul>';
    for (const [key, value] of entries) {
        html += '<li>' + key + ' - Frequência: ' + value + '</li>';
    }
    html += '</ul>';

    return html;
}