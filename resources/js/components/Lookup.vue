<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import { api } from '../api';
const props=defineProps(['entity','modelValue','id','label']);
const emit=defineEmits(['update:modelValue']);
const options=ref([]), query=ref(''), loading=ref(false), error=ref('');
let timer, sequence=0;
async function load() {
    const current=++sequence;
    loading.value=true; error.value='';
    try {
        const params=new URLSearchParams({q:query.value});
        if(props.modelValue) params.set('selected',props.modelValue);
        const data=await api(`/opcoes/${props.entity}?${params}`);
        if(current===sequence) options.value=data;
    } catch(e) { if(current===sequence) error.value=e.message; }
    finally { if(current===sequence) loading.value=false; }
}
function search() { clearTimeout(timer);timer=setTimeout(load,250); }
onMounted(load);
watch(()=>props.modelValue,value=>{if(value && !options.value.some(o=>String(o.id)===String(value))) load();});
onBeforeUnmount(()=>{clearTimeout(timer);sequence++;});
</script>
<template>
    <div class="lookup">
        <input v-model="query" type="search" :aria-label="`Pesquisar ${label}`" placeholder="Digite para pesquisar opções" @input="search">
        <select :id="id" :value="modelValue ?? ''" required @change="emit('update:modelValue',$event.target.value)">
            <option value="">Selecione…</option><option v-for="option in options" :key="option.id" :value="option.id">{{ option.label }}</option>
        </select>
        <small v-if="loading" role="status">Buscando opções…</small>
        <small v-else-if="error" class="field-error" role="alert">{{ error }} <button type="button" class="link-button" @click="load">Repetir</button></small>
        <small v-else>Até 50 resultados. Refine a pesquisa se necessário.</small>
    </div>
</template>
