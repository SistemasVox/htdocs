document.addEventListener('DOMContentLoaded', function() {
    // Função para carregar dados de mensagens e serviços
    async function loadData() {
        try {
            const responseMensagens = await axios.get('api.php?get_mensagens=1');
            const mensagensList = document.getElementById('mensagens');
            if (mensagensList) {
                mensagensList.innerHTML = '';
                responseMensagens.data.forEach((mensagem, index) => {
                    const li = document.createElement('li');
                    li.textContent = `${index + 1}. ${mensagem.mensagem} (Formal: ${mensagem.formal})`;
                    mensagensList.appendChild(li);
                });
            }

            const responseServicos = await axios.get('api.php?get_servicos=1');
            const servicosList = document.getElementById('servicos');
            if (servicosList) {
                servicosList.innerHTML = '';
                responseServicos.data.forEach((servico, index) => {
                    const li = document.createElement('li');
                    li.textContent = `${index + 1}. ${servico.descricao}`;
                    servicosList.appendChild(li);
                });
            }
        } catch (error) {
            console.error('Erro ao carregar dados: ', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: 'Erro ao carregar dados. Por favor, tente novamente.'
            });
        }
    }

    // Função para carregar mensagens no select de associação
    async function loadMensagensSelect() {
        try {
            const response = await axios.get('api.php?get_mensagens=1');
            const mensagemSelect = document.getElementById('mensagemSelect');
            response.data.forEach(mensagem => {
                const option = document.createElement('option');
                option.value = mensagem.id;
                option.textContent = mensagem.mensagem;
                mensagemSelect.appendChild(option);
            });
        } catch (error) {
            console.error('Erro ao carregar mensagens: ', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: 'Erro ao carregar as mensagens. Por favor, tente novamente.'
            });
        }
    }

    // Função para carregar serviços no select de associação
    async function loadServicosSelect() {
        try {
            const response = await axios.get('api.php?get_servicos=1');
            const servicoSelect = document.getElementById('servicoSelect');
            response.data.forEach(servico => {
                const option = document.createElement('option');
                option.value = servico.id;
                option.textContent = servico.descricao;
                servicoSelect.appendChild(option);
            });
        } catch (error) {
            console.error('Erro ao carregar serviços: ', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: 'Erro ao carregar os serviços. Por favor, tente novamente.'
            });
        }
    }

    // Função para associar uma mensagem a um serviço
    async function associarMensagemServico(event) {
        event.preventDefault();
        const mensagemId = document.getElementById('mensagemSelect').value;
        const servicoId = document.getElementById('servicoSelect').value;

        const formData = new FormData();
        formData.append('mensagem_id', mensagemId);
        formData.append('servico_id', servicoId);

        try {
            const response = await axios.post('api.php', formData);
            if (response.data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso',
                    text: 'Associação realizada com sucesso!'
                });
                // Limpar os campos do formulário após a associação
                /* document.getElementById('mensagemSelect').selectedIndex = 0; */
                document.getElementById('servicoSelect').selectedIndex = 0;
				loadData();
				loadTabelaStatus();
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Aviso',
                    text: 'Erro ao associar mensagem a serviço: ' + response.data.message
                });
            }
        } catch (error) {
            console.error('Erro ao enviar a requisição: ', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: 'Erro ao realizar a associação. Por favor, tente novamente.'
            });
        }
    }

    // Função para carregar informações das tabelas
    async function loadTabelaStatus() {
        try {
            const responseMensagens = await axios.get('api.php?get_mensagens=1');
            const responseServicos = await axios.get('api.php?get_servicos=1');
            const responseMensagemServico = await axios.get('api.php?get_mensagem_servico=1');
            const responseMensagemServicoQuantidade = await axios.get('api.php?get_mensagem_servico_quantidade=1');

            const mensagensTotal = responseMensagens.data.length;
            const servicosTotal = responseServicos.data.length;
            const associacoesTotal = responseMensagemServico.data.length;

            let mensagensSemAssociacao = 0;
            let servicosSemAssociacao = 0;

            if (Array.isArray(responseMensagens.data) && Array.isArray(responseMensagemServico.data)) {
                responseMensagens.data.forEach(mensagem => {
                    const mensagemAssociada = responseMensagemServico.data.some(associacao => associacao.id_mensagem === mensagem.id);
                    if (!mensagemAssociada) {
                        mensagensSemAssociacao++;
                    }
                });
            }

            if (Array.isArray(responseServicos.data) && Array.isArray(responseMensagemServico.data)) {
                responseServicos.data.forEach(servico => {
                    const servicoAssociado = responseMensagemServico.data.some(associacao => associacao.id_servico === servico.id);
                    if (!servicoAssociado) {
                        servicosSemAssociacao++;
                    }
                });
            }

            // Inserir os dados na tabela
            const tabelaStatus = document.getElementById('tabelaStatus');
            tabelaStatus.innerHTML = `
            <tr>
                <td>Mensagem</td>
                <td>${mensagensTotal}</td>
                <td>${mensagensSemAssociacao}</td>
                <td>${mensagensTotal - mensagensSemAssociacao}</td>
            </tr>
            <tr>
                <td>Serviço</td>
                <td>${servicosTotal}</td>
                <td>${servicosSemAssociacao}</td>
                <td>${servicosTotal - servicosSemAssociacao}</td>
            </tr>
            <tr>
                <td>MensagemServico</td>
                <td>${associacoesTotal}</td>
                <td>-</td>
                <td>-</td>
            </tr>
        `;

            // Preencher a quantidade de mensagens por serviço na tabela
            if (Array.isArray(responseMensagemServicoQuantidade.data)) {
                const mensagemServicoTable = document.getElementById('mensagemServicoTable');
                mensagemServicoTable.innerHTML = `
                <tr>
                    <th>Serviço</th>
                    <th>Quantidade de Mensagens</th>
                </tr>
            `;

                // Obter um objeto com o nome de todos os serviços a partir do ID
                const serviceNames = {};
                for (const servico of responseServicos.data) {
                    serviceNames[servico.id] = servico.descricao;
                }

                // Preencher a tabela com o nome do serviço e a quantidade de mensagens
                for (const item of responseMensagemServicoQuantidade.data) {
                    mensagemServicoTable.innerHTML += `
                    <tr>
                        <td>${serviceNames[item.id_servico]}</td>
                        <td>${item.quantidade}</td>
                    </tr>
                `;
                }
            }

            console.log("Informações das tabelas carregadas com sucesso!");
        } catch (error) {
            console.error('Erro ao carregar informações das tabelas: ', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: 'Erro ao carregar informações das tabelas. Por favor, tente novamente.'
            });
        }
    }



    // Carregar dados na página
    loadData();

    // Carregar mensagens e serviços nos selects de associação
    loadMensagensSelect();
    loadServicosSelect();

    // Carregar informações das tabelas na página
    loadTabelaStatus();

    // Event listener para associar mensagem a serviço
    document.getElementById('associarForm').addEventListener('submit', associarMensagemServico);
});