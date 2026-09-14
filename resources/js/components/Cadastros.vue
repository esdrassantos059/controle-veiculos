<script setup>
import { ref, reactive, computed, onMounted, nextTick } from 'vue';
import { api, display } from '../api';
import { definitions } from '../definitions';
import Lookup from './Lookup.vue';
const props=defineProps(['entity']);
const def=computed(()=>definitions[props.entity]);
const data=ref([]),loading=ref(true),error=ref(''),notice=ref(''),q=ref(''),page=ref(1),last=ref(1),total=ref(0);
const dialog=ref(),confirmDialog=ref(),editing=ref(null),deleting=ref(null),saving=ref(false),formError=ref(''),fieldErrors=ref({});
const form=reactive({});
let initial='';
const params=new URLSearchParams(location.search);
const parentKey=props.entity==='carros'?'pessoa_id':props.entity==='revisoes'?'carro_id':null;
const parentId=parentKey?params.get(parentKey):null;
const parentName=ref('');
const today=new Date().toLocaleDateString('en-CA');
const year=new Date().getFullYear()+1;
function value(row,key) { return display(key.split('.').reduce((v,k)=>v?.[k],row),key); }
async function load(number=page.value) {
    loading.value=true;error.value='';
    try {
        const search=new URLSearchParams({page:number,q:q.value});
        if(parentId) search.set(parentKey,parentId);
        const result=await api(`/${props.entity}?${search}`);
        if(result.data.length===0 && number>1) return await load(number-1);
        data.value=result.data;page.value=result.current_page;last.value=result.last_page;total.value=result.total;
    } catch(e) {error.value=e.message;}
    finally {loading.value=false;}
}
async function open(record=null) {
    editing.value=record;fieldErrors.value={};formError.value='';
    Object.keys(form).forEach(k=>delete form[k]);
    for(const field of def.value.fields) form[field.key]=record?.[field.key] ?? (field.key===parentKey && parentId ? parentId : '');
    for(const field of def.value.fields.filter(f=>f.type==='date')) form[field.key]=String(form[field.key]).slice(0,10);
    initial=JSON.stringify(form);
    await nextTick();dialog.value.showModal();
}
function close() {
    if(saving.value) return;
    if(JSON.stringify(form)!==initial && !window.confirm('Descartar as alterações deste formulário?')) return;
    dialog.value.close();editing.value=null;
}
async function save() {
    saving.value=true;fieldErrors.value={};formError.value='';
    try {
        await api(`/${props.entity}${editing.value?'/'+editing.value.id:''}`,{method:editing.value?'PUT':'POST',body:JSON.stringify(form)});
        notice.value=editing.value?'Alterações salvas.':'Cadastro criado com sucesso.';
        dialog.value.close();await load();
    } catch(e) {fieldErrors.value=e.fields||{};formError.value=Object.keys(e.fields||{}).length?'Confira os campos destacados.':e.message;}
    finally {saving.value=false;}
}
function askDelete(record) {deleting.value=record;formError.value='';confirmDialog.value.showModal();}
async function remove() {
    saving.value=true;formError.value='';
    try { await api(`/${props.entity}/${deleting.value.id}`,{method:'DELETE'});confirmDialog.value.close();notice.value='Registro excluído.';await load(); }
    catch(e) {formError.value=e.message;}
    finally {saving.value=false;}
}
onMounted(async()=>{
    load();
    if(parentId) {
        try { const p=await api(`/${props.entity==='carros'?'pessoas':'carros'}/${parentId}`);parentName.value=p.nome||p.placa; }
        catch {parentName.value=`ID ${parentId}`;}
    }
});
</script>
<template>
    <div class="page-heading"><div><p class="eyebrow">CADASTROS</p><h1>{{ def.title }}</h1><p class="description">{{ def.description }}</p></div><button class="primary" @click="open()">+ Novo cadastro</button></div>
    <div v-if="parentId" class="filter-banner">Exibindo vínculos de <strong>{{ parentName || '…' }}</strong><a :href="`/${entity}`">Mostrar todos</a></div>
    <div v-if="notice" class="notice" role="status">{{ notice }}<button class="link-button" @click="notice=''" aria-label="Fechar aviso">×</button></div>
    <section class="panel">
        <form class="toolbar" @submit.prevent="load(1)"><label class="search"><span class="sr-only">{{ def.search }}</span><input v-model="q" type="search" :placeholder="def.search" maxlength="150"></label><button :disabled="loading">Buscar</button><button type="button" :disabled="loading" @click="q='';load(1)">Limpar</button><span class="badge" v-if="!loading && !error">{{ total }} {{ total === 1 ? 'registro' : 'registros' }}</span></form>
        <p v-if="loading" class="state" role="status">Carregando registros…</p>
        <div v-else-if="error" class="state error" role="alert"><p>{{ error }}</p><button @click="load()">Tentar novamente</button></div>
        <div v-else-if="!data.length" class="state"><div class="empty-icon" aria-hidden="true">○</div><h2>Nenhum registro encontrado</h2><p>Revise a busca ou crie o primeiro cadastro.</p><button class="primary" @click="open()">Cadastrar {{ def.singular }}</button></div>
        <template v-else><div class="table-scroll" tabindex="0" :aria-label="`Tabela de ${def.title}`"><table><thead><tr><th v-for="column in def.columns" :key="column[0]" scope="col">{{ column[1] }}</th><th scope="col">Ações</th></tr></thead><tbody>
        <tr v-for="record in data" :key="record.id"><td v-for="column in def.columns" :key="column[0]">{{ value(record,column[0]) }}</td><td><div class="row-actions">
            <a v-if="entity==='pessoas'" :href="`/carros?pessoa_id=${record.id}`">Veículos</a><a v-if="entity==='carros'" :href="`/revisoes?carro_id=${record.id}`">Revisões</a>
            <button class="link-button" @click="open(record)" :aria-label="`Editar ${record.nome || record.placa || 'revisão '+record.id}`">Editar</button><button class="link-button danger-text" @click="askDelete(record)" :aria-label="`Excluir ${record.nome || record.placa || 'revisão '+record.id}`">Excluir</button>
        </div></td></tr></tbody></table></div><footer class="pagination"><span>Página {{ page }} de {{ last }}</span><div><button :disabled="page<=1" @click="load(page-1)">Anterior</button><button :disabled="page>=last" @click="load(page+1)">Próxima</button></div></footer></template>
    </section>

    <dialog ref="dialog" class="animate__animated animate__fadeIn" aria-labelledby="form-title" @cancel.prevent="close">
        <form @submit.prevent="save"><div class="dialog-heading"><div><p class="eyebrow">{{ editing?'EDITAR':'NOVO CADASTRO' }}</p><h2 id="form-title">{{ editing?'Editar':'Cadastrar' }} {{ def.singular }}</h2></div><button type="button" class="link-button" aria-label="Fechar formulário" :disabled="saving" @click="close">×</button></div>
        <p class="hint">Todos os campos são obrigatórios.</p><div v-if="formError" class="error-box" role="alert">{{ formError }}</div>
        <div class="form-grid"><div v-for="field in def.fields" :key="`${editing?.id ?? 'new'}-${field.key}`" class="field" :class="{'wide':field.type==='lookup'||field.key==='nome'}">
            <label :for="`field-${field.key}`">{{ field.label }}</label>
            <Lookup v-if="field.type==='lookup'" v-model="form[field.key]" :entity="field.entity" :id="`field-${field.key}`" :label="field.label"/>
            <select v-else-if="field.type==='select'" :id="`field-${field.key}`" v-model="form[field.key]" required><option value="">Selecione…</option><option v-for="option in field.options" :value="option[0]">{{ option[1] }}</option></select>
            <input v-else :id="`field-${field.key}`" v-model="form[field.key]" :type="field.type||'text'" :maxlength="field.max" :min="field.min" :max="field.type==='date'?today:field.type==='number'?year:undefined" :placeholder="field.placeholder" required :aria-invalid="!!fieldErrors[field.key]" :aria-describedby="fieldErrors[field.key]?`error-${field.key}`:undefined">
            <small v-if="fieldErrors[field.key]" :id="`error-${field.key}`" class="field-error">{{ fieldErrors[field.key][0] }}</small>
        </div></div><footer class="dialog-footer"><button type="button" :disabled="saving" @click="close">Cancelar</button><button class="primary" :disabled="saving">{{ saving?'Salvando…':'Salvar cadastro' }}</button></footer></form>
    </dialog>
    <dialog ref="confirmDialog" class="small-dialog" aria-labelledby="delete-title" @cancel="saving && $event.preventDefault()"><h2 id="delete-title">Excluir {{ def.singular }}?</h2><p>Você está excluindo <strong>{{ deleting?.nome || deleting?.placa || 'a revisão #'+deleting?.id }}</strong>. Esta ação não pode ser desfeita.</p><p v-if="formError" class="error-box" role="alert">{{ formError }}</p><footer class="dialog-footer"><button :disabled="saving" @click="confirmDialog.close()">Cancelar</button><button class="danger" :disabled="saving" @click="remove">{{ saving?'Excluindo…':'Confirmar exclusão' }}</button></footer></dialog>
</template>
