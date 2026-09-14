export async function api(url, options = {}) {
    const response = await fetch(`/api${url}`, {
        ...options,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            ...options.headers,
        },
    });
    const data = response.status === 204 ? null : await response.json().catch(() => ({}));
    if (!response.ok) {
        const error = new Error(response.status === 419 ? 'Sua sessão expirou. Atualize a página antes de salvar.' :
            response.status >= 500 ? 'Não foi possível acessar os dados. Verifique a conexão e tente novamente.' :
                data.message || 'Não foi possível concluir a operação.');
        error.fields = data.errors || {};
        throw error;
    }
    return data;
}

export function display(value, key = '') {
    if (value === null || value === undefined || value === '') return 'Não informado';
    if (key === 'genero') return ({ M: 'Masculino', F: 'Feminino', 'N/I': 'Não informado' })[String(value).toUpperCase()] || value;
    if (key.includes('data') && !key.includes('distintas') || ['ultima_revisao','proxima_revisao'].includes(key)) {
        const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (match) return `${match[3]}/${match[2]}/${match[1]}`;
    }
    return value;
}

export const titles = { id:'ID',pessoa_id:'ID da pessoa',nome:'Nome',proprietario:'Proprietário',marca:'Marca',modelo:'Modelo',ano:'Ano-modelo',placa:'Placa',genero:'Gênero',data_nascimento:'Nascimento',idade:'Idade',idade_media:'Idade média',email:'E-mail',telefone:'Telefone',data_revisao:'Data da revisão',total:'Total',masculino:'Masculino',feminino:'Feminino',nao_informado:'Não informado',datas_distintas:'Datas distintas',ultima_revisao:'Última revisão',media_dias:'Média (dias)',proxima_revisao:'Próxima revisão',situacao:'Situação' };
