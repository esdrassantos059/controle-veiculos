export const definitions = {
    pessoas: { title:'Pessoas', singular:'pessoa',description:'Proprietários, contatos e seus veículos.', search:'Buscar pelo nome',
        fields:[{key:'nome',label:'Nome completo',max:150},{key:'genero',label:'Gênero',type:'select',options:[['M','Masculino'],['F','Feminino']]},{key:'data_nascimento',label:'Data de nascimento',type:'date'},{key:'email',label:'E-mail',type:'email',max:150},{key:'telefone',label:'Telefone com DDD',type:'tel',max:20}],
        columns:[['nome','Nome'],['genero','Gênero'],['data_nascimento','Nascimento'],['email','E-mail'],['telefone','Telefone'],['carros_count','Veículos']] },
    marcas: {title:'Marcas',singular:'marca',description:'Uma marca, um cadastro. Organize os fabricantes dos veículos.',search:'Buscar marca',
        fields:[{key:'nome',label:'Nome da marca',max:100}],columns:[['nome','Marca'],['carros_count','Veículos']]},
    carros: {title:'Veículos',singular:'veículo',description:'Veículos vinculados a seus proprietários e marcas.',search:'Buscar placa ou modelo',
        fields:[{key:'pessoa_id',label:'Proprietário',type:'lookup',entity:'pessoas'},{key:'marca_id',label:'Marca',type:'lookup',entity:'marcas'},{key:'modelo',label:'Modelo',max:100},{key:'ano',label:'Ano-modelo',type:'number',min:1960},{key:'placa',label:'Placa',max:8,placeholder:'ABC-1234 ou ABC1D23'}],
        columns:[['placa','Placa'],['modelo','Modelo'],['ano','Ano-modelo'],['pessoa.nome','Proprietário'],['marca.nome','Marca'],['revisoes_count','Revisões']]},
    revisoes: {title:'Revisões',singular:'revisão',description:'Histórico de revisões realizadas em cada veículo.',search:'Buscar pela placa',
        fields:[{key:'carro_id',label:'Veículo',type:'lookup',entity:'carros'},{key:'data_revisao',label:'Data da revisão',type:'date'}],
        columns:[['data_revisao','Data'],['carro.placa','Placa'],['carro.modelo','Modelo'],['carro.pessoa.nome','Proprietário']]},
};
