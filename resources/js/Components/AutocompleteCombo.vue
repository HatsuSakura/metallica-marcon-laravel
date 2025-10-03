<template>
  <div class="relative">
    <input
      ref="inputEl"
      :placeholder="placeholder"
      class="input input-sm input-bordered w-full"
      v-model="q"
      @focus="open = true"
      @keydown.down.prevent="move(+1)"
      @keydown.up.prevent="move(-1)"
      @keydown.enter.prevent="pickIndex(activeIndex)"
      @blur="onBlur"
    />
    <ul v-if="open && filtered.length"
        class="menu bg-base-100 rounded-box shadow absolute z-10 mt-1 w-full max-h-60 overflow-auto">
      <li v-for="(it,i) in filtered" :key="it.id">
        <a :class="i===activeIndex ? 'active' : ''"
           @mousedown.prevent="pick(it)">{{ it.name }}
           <span class="badge badge-ghost ml-2">{{ it.type }}</span>
        </a>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted } from 'vue'

const props = defineProps({
  items: { type: Array, required: true },        // [{id,name,type}]
  placeholder: { type: String, default: 'Cerca…' },
  modelValue: { type: Object, default: null },   // {id,name,type} | null
  allowTypes: { type: Array, default: () => ['material','component'] }, // opz.
  initialText: { type: String, default: '' },
})
const emit = defineEmits([
  'update:modelValue',
  'enter',
  'input-text',
])

const q = ref('')
const open = ref(false)
const activeIndex = ref(0)
let internalChange = false // flag per sopprimere l’input-text quando il cambio è interno
/* Ref all’input + metodo focus() */
const inputEl = ref(null)
function focus() {
  inputEl.value?.focus()
}
defineExpose({ focus })

const filtered = computed(() => {
  const needle = q.value.trim().toLowerCase()
  const base = props.items.filter(x => props.allowTypes.includes(x.type))
  if (!needle) return base.slice(0, 50)         // hard cap per performance
  return base.filter(x => x.name.toLowerCase().includes(needle)).slice(0, 50)
})

onMounted(() => {
  // Se non ho una selezione, mostro il testo iniziale
  q.value = props.modelValue?.name ?? props.initialText ?? ''
})


watch(
  () => props.modelValue,
  async (v) => { 
    internalChange = true
    q.value = v?.name ?? ''
    await nextTick()
    internalChange = false 
  }, 
  { immediate:true }
)


// debounce 250ms
let t
watch(q, (val) => {
  if (internalChange) return
  clearTimeout(t)
  t = setTimeout(() => emit('input-text', val), 250)
})


function pick(it){
  internalChange = true
  emit('select', it)
  emit('update:modelValue', it)
  q.value = it.name
  open.value = false
  nextTick(() => { internalChange = false })
}

function pickIndex(i){
  const it = filtered.value[i]
  if (it) {
    pick(it)
  } else {
    const text = q.value.trim()
    emit('enter', text)
    emit('commitText', text)        // <-- NEW: commit esplicito anche su Enter
  }
}

function move(delta){
  const n = filtered.value.length
  if (!n) return
  activeIndex.value = (((activeIndex.value + delta) % n) + n) % n
}

// ↪ al blur comunichiamo il testo “finale”
function onBlur() {
  const text = q.value.trim()
  // setTimeout per non interferire col mousedown sulla lista
  setTimeout(() => {
    open.value = false
    emit('commitText', text)        // <-- NEW
  }, 0)
}

</script>
