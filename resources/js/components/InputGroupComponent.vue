<template>
        <div class="input-group has-validation">
            <span class="input-group-text">{{label}}</span>
            <input 
                class="form-control" 
                :class="class" 
                :disabled="disabled"
                :id="id"
                :filename="filename"
                :maxlength="maxlength"
                :type="type" 
                :step="step" 
                :name="name" 
                :value="value" 
                :required="required"
                v-money="monay"
                v-maska 
                :data-maska="mascara"
                >
            <div class="invalid-feedback text-start">{{ message }}</div>
        </div>
            <img v-if="filename" :src="'/storage/'+filename"  class="img-thumbnail">
</template>

<script>
    import { vMaska } from "maska"
    export default {
        props: [ 
            'class',
            'disabled',
            'id',
            'filename',
            'label',
            'mascara',
            'maxlength',
            'message',
            'monay',
            'name', 
            'required',
            'step',
            'style',
            'type',
            'value',
        ],
        directives: { 
            maska: vMaska 
        },
    }
</script>