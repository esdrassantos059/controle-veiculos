<script setup>
import { ref,computed,onMounted } from 'vue';
import { api,display,titles } from '../api';
const catalog=ref({}),selected=ref('veiculos'),rows=ref([]),meta=ref(null),loading=ref(true),error=ref(''),page=ref(1);
const localDate=new Date(),year=localDate.getFullYear(),month=String(localDate.getMonth()+1).padStart(2,'0'),day=String(localDate.getDate()).padStart(2,'0');
const start=ref(`${year}-${month}-01`),end=ref(`${year}-${month}-${day}`);
const columns=computed(()=>rows.value.length?Object.keys(rows.value[0]):[]);
const last=computed(()=>Math.max(1,Math.ceil(rows.value.length/20)));
const paged=computed(()=>rows.value.slice((page.value-1)*20,page.value*20));
const bars=computed(()=>{
    if(!meta.value) return [];
    const {mode,label,value}=meta.value, result=new Map();
    for(const row of rows.value) {
        let text=String(display(row[label],label));
        if(['nome','proprietario'].includes(label)) text+=` (#${row.pessoa_id ?? row.id ?? ''})`;
        if(mode==='gender') {
            for(const [key,suffix] of [['masculino','M'],['feminino','F'],['nao_informado','N/I']]) result.set(`${text} · ${suffix}`,Number(row[key]));
        } else if(mode==='count') result.set(text,(result.get(text)||0)+1);
        else if(row[value]!==null && Number.isFinite(Number(row[value]))) result.set(text,Number(row[value]));
    }
    return [...result].map(([label,value])=>({label,value})).sort((a,b)=>b.value-a.value);
});
const chart=computed(()=>bars.value.slice(0,20));
const max=computed(()=>Math.max(1,...chart.value.map(x=>x.value)));
let sequence=0;
async function load() {
    const id=++sequence;loading.value=true;error.value='';page.value=1;
    try {
        const query=new URLSearchParams({inicio:start.value,fim:end.value});
        const result=await api(`/relatorios/${selected.value}?${query}`);
        if(id===sequence){ rows.value=result.data;meta.value=result.meta; }
    } catch(e){if(id===sequence)error.value=e.message;}
    finally {if(id===sequence)loading.value=false;}
}
async function initialize() {
    loading.value=true;error.value='';
    try {catalog.value=await api('/relatorios');await load();}catch(e){error.value=e.message;loading.value=false;}
}
function csv() {
    const quote=v=>'"'+String(v??'').replace(/^[=+@\-\t\r]/,"'$&").replaceAll('"','""')+'"';
    const text=[columns.value.map(k=>quote(titles[k]||k)).join(';'),...rows.value.map(row=>columns.value.map(k=>quote(row[k])).join(';'))].join('\r\n');
    const url=URL.createObjectURL(new Blob(['\ufeff'+text],{type:'text/csv;charset=utf-8;'}));
    const a=document.createElement('a');a.href=url;a.download=`relatorio-${selected.value}.csv`;a.click();setTimeout(()=>URL.revokeObjectURL(url),1000);
}
onMounted(initialize);
</script>
<template>
    <div class="page-heading"><div><p class="eyebrow">INFORMAÇÃO PARA DECIDIR</p><h1>Relatórios</h1><p class="description">Explore os veículos, proprietários e o histórico de revisões.</p></div><button :disabled="loading||!!error||!rows.length" @click="csv">Exportar CSV</button></div>
    <form class="panel report-controls" @submit.prevent="load"><div class="field"><label for="report">Escolha um relatório</label><select id="report" v-model="selected" @change="load"><option v-for="(report,key) in catalog" :value="key">{{ report.title }}</option></select></div><template v-if="selected==='revisoes-periodo'"><div class="field"><label for="start">De</label><input id="start" v-model="start" type="date" required></div><div class="field"><label for="end">Até</label><input id="end" v-model="end" type="date" :min="start" required></div></template><button class="primary" :disabled="loading">{{ loading?'Consultando…':'Atualizar relatório' }}</button></form>
    <div v-if="loading" class="panel state" role="status">Preparando o relatório…</div>
    <div v-else-if="error" class="panel state error" role="alert"><p>{{ error }}</p><button @click="initialize">Tentar novamente</button></div>
    <template v-else-if="meta">
        <section class="panel chart-panel animate__animated animate__fadeIn" aria-labelledby="chart-title"><div class="panel-heading"><div><p class="eyebrow">VISÃO GRÁFICA</p><h2 id="chart-title">{{ meta.chart }}</h2></div><span class="badge">{{ rows.length }} {{ rows.length === 1 ? 'linha' : 'linhas' }}</span></div>
            <div v-if="!chart.length" class="state"><h3>Sem dados suficientes para o gráfico</h3><p>Cadastre registros ou selecione outro período.</p></div>
            <div v-else class="bars" role="list" :aria-label="meta.chart"><div v-for="bar in chart" :key="bar.label" class="bar-row" role="listitem"><span class="bar-label">{{ bar.label }}</span><div class="bar-track"><div class="bar-fill" :style="{width:`${bar.value/max*100}%`}"></div></div><strong>{{ bar.value.toLocaleString('pt-BR',{maximumFractionDigits:2}) }}</strong></div></div>
            <p v-if="bars.length>20" class="hint chart-note">O gráfico mostra os 20 maiores valores. A tabela e o CSV contêm todos os resultados.</p>
        </section>
        <p v-if="['intervalos','proximas'].includes(selected)" class="hint">Estimativa por pessoa, usando os veículos atualmente vinculados. Revisões no mesmo dia contam como uma data. São necessárias duas datas distintas. A previsão não substitui o plano de manutenção do fabricante.</p>
        <section class="panel"><div class="panel-heading"><h2>{{ meta.title }}</h2></div><div v-if="!rows.length" class="state">Nenhum resultado para esta consulta.</div><template v-else><div class="table-scroll" tabindex="0" aria-label="Tabela do relatório"><table><thead><tr><th v-for="key in columns" :key="key" scope="col">{{ titles[key]||key }}</th></tr></thead><tbody><tr v-for="(row,index) in paged" :key="index"><td v-for="key in columns" :key="key">{{ display(row[key],key) }}</td></tr></tbody></table></div><footer class="pagination"><span>Página {{ page }} de {{ last }}</span><div><button :disabled="page<=1" @click="page--">Anterior</button><button :disabled="page>=last" @click="page++">Próxima</button></div></footer></template></section>
    </template>
</template>
