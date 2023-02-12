<template>
    <div class="input-group"
        :class="classinputgroup"
        >
        <span v-if="disabled != ''" class="input-group-text">Filtro:</span>
        <input v-if="disabled != ''" type="text" class="form-control" v-model="filtro">
        <span class="input-group-text">{{label}}</span>
        <select v-if="disabled != ''"
            class="form-select w-50 rounded-end"
            :class="class"
            :disabled="disabled"
            :id="id" 
            :name="name" 
            :required="required" 
            >
            <option v-for="opcao in listafiltrada" :value="opcao.value" :selected="opcao.value == old_id">{{opcao.text}}</option>
        </select>
        <input v-if="disabled == ''" :value="value" 
            class="form-control w-50 rounded-end" disabled>
        <span v-if="message != ''"
            class="text-start"
            :class="classmessage"
            >{{ message }}
        </span>
    </div>
</template>

<script>
    export default {
        mounted() { 
            this.listafiltrada[0] = this.opcoes[0] = {'value' : '', 'text' : this.option};
            this.listafiltrada.push.apply(this.listafiltrada,JSON.parse(this.options))
            this.opcoes.push.apply(this.opcoes,JSON.parse(this.options))
        },
        props: [
            'class',
            'classinputgroup',
            'classmessage',
            'disabled',
            'id',
            'label',
            'message',
            'name',
            'option',
            'options',
            'old_id',
            'required',
            'value',
        ],
        data(){
            return{
                filtro: null,
                opcoes: [],
                listafiltrada: [],
            }
        },
        watch:{ //Funções que monitoram qualquer mudança no valor // As funções devem ter o mesmo nome do atributo
            filtro(valorNovo){
                this.listafiltrada = this.opcoes.filter(opcao => opcao.text.toLowerCase().match(valorNovo.toLowerCase()))
            }
        },
    }
</script>