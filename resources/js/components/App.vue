<script setup>
import {ref,onMounted} from 'vue';
import Cadastros from './Cadastros.vue';
import Relatorios from './Relatorios.vue';
import {api} from '../api';
const menu=[['inicio','Visão geral','◈'],['pessoas','Pessoas','♙'],['carros','Veículos','▱'],['marcas','Marcas','◇'],['revisoes','Revisões','✓'],['relatorios','Relatórios','▥']];
const page=location.pathname.split('/')[1]||'inicio';
const summary=ref(null),error=ref('');
async function load(){error.value='';try{summary.value=await api('/resumo');}catch(e){error.value=e.message;}}
onMounted(()=>{document.title=`${menu.find(x=>x[0]===page)?.[1]||'Sistema'} | Revisar`;if(page==='inicio')load();});
</script>
<template>
    <div class="shell"><aside class="sidebar"><a href="/inicio" class="brand"><span class="brand-mark">R</span><span>Revisar<small>Controle de veículos</small></span></a><p class="nav-caption">ESPAÇO DE TRABALHO</p><nav aria-label="Navegação principal"><a v-for="[key,label,icon] in menu" :href="`/${key}`" :aria-current="page===key?'page':undefined" :class="{active:page===key}"><span aria-hidden="true">{{ icon }}</span>{{ label }}</a></nav><div class="sidebar-note"><span class="status-dot"></span> Organização em cada revisão</div></aside>
    <div class="workspace"><header class="topbar"><span>Gestão de revisões de veículos</span><span class="top-label">Seu histórico, em um só lugar</span></header><main>
        <template v-if="page==='inicio'"><div class="page-heading"><div><p class="eyebrow">BEM-VINDO AO REVISAR</p><h1>Uma visão do seu cadastro.</h1><p class="description">Acompanhe os registros e encontre o próximo passo.</p></div><a class="button primary" href="/revisoes">Registrar revisão</a></div><div v-if="error" class="error-box" role="alert">{{ error }} <button @click="load">Tentar novamente</button></div><p v-else-if="!summary" role="status">Carregando resumo…</p><div v-else class="stat-grid"><a v-for="[key,label,icon] in menu.filter(x=>['pessoas','carros','marcas','revisoes'].includes(x[0]))" :href="`/${key}`" class="stat-card"><span class="stat-icon" aria-hidden="true">{{ icon }}</span><strong>{{ summary[key] }}</strong><span>{{ label }}</span><small>Abrir cadastro →</small></a></div><section class="panel intro-panel"><p class="eyebrow">FLUXO DE TRABALHO</p><h2>Do proprietário ao histórico completo</h2><div class="steps"><a href="/pessoas"><b>01</b><h3>Cadastre a pessoa</h3><p>Registre os dados e contatos do proprietário.</p></a><a href="/carros"><b>02</b><h3>Vincule o veículo</h3><p>Selecione a marca e informe modelo, ano e placa.</p></a><a href="/revisoes"><b>03</b><h3>Registre a revisão</h3><p>Consulte o histórico e acompanhe os relatórios.</p></a></div></section></template>
        <Relatorios v-else-if="page==='relatorios'"/>
        <Cadastros v-else :entity="page"/>
        <footer class="page-footer">Revisar · Controle de revisões de veículos</footer>
    </main></div></div>
</template>
