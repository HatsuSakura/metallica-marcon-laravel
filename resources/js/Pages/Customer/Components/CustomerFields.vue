<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-2">
      <div class="col-span-1 xl:col-span-1">
        <label class="label" for="is_occasional_customer">Cliente Occasionale</label>
        <input id="is_occasional_customer" v-model="form.is_occasional_customer" type="checkbox" class="toggle"  :aria-invalid="!!form.errors.is_occasional_customer" name="is_occasional_customer"/>
      </div>

      <div class="col-span-1 sm:col-span-2 xl:col-span-5">
        <label class="label" for="registry-customer-form-company_name">Ragione sociale</label>
        <input v-model="form.company_name" type="text" class="input"  id="registry-customer-form-company_name" :aria-invalid="!!form.errors.company_name" :aria-describedby="form.errors.company_name ? 'registry-customer-form-company_name-error' : undefined" name="company_name"/>
        <div class="input-error" v-if="form.errors.company_name" id="registry-customer-form-company_name-error" role="alert">
          {{ form.errors.company_name }}
        </div>
      </div>

      <div class="col-span-1 xl:col-span-3">
        <label class="label" for="registry-customer-form-vat_number">Partita Iva</label>
        <input v-model="form.vat_number" type="text" class="input"  id="registry-customer-form-vat_number" :aria-invalid="!!form.errors.vat_number" :aria-describedby="form.errors.vat_number ? 'registry-customer-form-vat_number-error' : undefined" name="vat_number"/>
        <div class="input-error" v-if="form.errors.vat_number" id="registry-customer-form-vat_number-error" role="alert">
          {{ form.errors.vat_number }}
        </div>
      </div>

      <div class="col-span-1 xl:col-span-3">
        <label class="label" for="registry-customer-form-tax_code">Codice Fiscale</label>
        <input v-model="form.tax_code" type="text" class="input"  id="registry-customer-form-tax_code" :aria-invalid="!!form.errors.tax_code" :aria-describedby="form.errors.tax_code ? 'registry-customer-form-tax_code-error' : undefined" name="tax_code"/>
        <div class="input-error" v-if="form.errors.tax_code" id="registry-customer-form-tax_code-error" role="alert">
          {{ form.errors.tax_code }}
        </div>
      </div>

      <div class="col-span-1 sm:col-span-2 xl:col-span-4">
        <label class="label" for="registry-customer-form-legal_address">Indirizzo legale</label>
        <input v-model="form.legal_address" type="text" class="input"  id="registry-customer-form-legal_address" :aria-invalid="!!form.errors.legal_address" :aria-describedby="form.errors.legal_address ? 'registry-customer-form-legal_address-error' : undefined" name="legal_address" @input="$emit('address-input')"/>
        <div class="input-error" v-if="form.errors.legal_address" id="registry-customer-form-legal_address-error" role="alert">
          {{ form.errors.legal_address }}
        </div>
      </div>

      <div class="col-span-1 xl:col-span-1">
        <label class="label" for="registry-customer-form-latitude">Lat</label>
        <input v-model="form.latitude" type="text" class="input" disabled="disabled"  id="registry-customer-form-latitude" :aria-invalid="!!form.errors.latitude" :aria-describedby="form.errors.latitude ? 'registry-customer-form-latitude-error' : undefined" name="latitude"/>
        <div class="input-error" v-if="form.errors.latitude" id="registry-customer-form-latitude-error" role="alert">
          {{ form.errors.latitude }}
        </div>
      </div>

      <div class="col-span-1 xl:col-span-1">
        <label class="label" for="registry-customer-form-longitude">Lng</label>
        <input v-model="form.longitude" type="text" class="input" disabled="disabled"  id="registry-customer-form-longitude" :aria-invalid="!!form.errors.longitude" :aria-describedby="form.errors.longitude ? 'registry-customer-form-longitude-error' : undefined" name="longitude"/>
        <div class="input-error" v-if="form.errors.longitude" id="registry-customer-form-longitude-error" role="alert">
          {{ form.errors.longitude }}
        </div>
      </div>

      <div class="col-span-1 sm:col-span-2 xl:col-span-6 min-h-64 pt-2"><slot name="map" /></div>

      <div class="col-span-1 xl:col-span-2">
        <label class="label" for="registry-customer-form-seller_id">Commerciale</label>
        <select v-model="form.seller_id" class="select select-bordered w-full max-w-xs" id="registry-customer-form-seller_id" :aria-invalid="!!form.errors.seller_id" :aria-describedby="form.errors.seller_id ? 'registry-customer-form-seller_id-error' : undefined" name="seller_id">
          <option :value="null" disabled>Seleziona il commerciale</option>
          <option v-for="manager in props.managers" :key="manager.id" :value="manager.id">
            {{ manager.name }}
          </option>
        </select>
        <div class="input-error" v-if="form.errors.seller_id" id="registry-customer-form-seller_id-error" role="alert">
          {{ form.errors.seller_id }}
        </div>
      </div>

      <div class="col-span-1 xl:col-span-2">
        <label class="label" for="registry-customer-form-sdi_code">Codice SDI</label>
        <input v-model="form.sdi_code" type="text" class="input"  id="registry-customer-form-sdi_code" :aria-invalid="!!form.errors.sdi_code" :aria-describedby="form.errors.sdi_code ? 'registry-customer-form-sdi_code-error' : undefined" name="sdi_code"/>
        <div class="input-error" v-if="form.errors.sdi_code" id="registry-customer-form-sdi_code-error" role="alert">
          {{ form.errors.sdi_code }}
        </div>
      </div>

      <div class="col-span-1 xl:col-span-2">
        <label class="label" for="registry-customer-form-business_type_id">Tipologia attività</label>
        <select v-model="form.business_type_id" class="select select-bordered w-full max-w-xs" id="registry-customer-form-business_type_id" :aria-invalid="!!form.errors.business_type_id" :aria-describedby="form.errors.business_type_id ? 'registry-customer-form-business_type_id-error' : undefined" name="business_type_id">
          <option :value="null">— nessuna —</option>
          <option v-for="bt in props.businessTypes" :key="bt.id" :value="bt.id">
            {{ bt.name }}
          </option>
        </select>
        <div class="input-error" v-if="form.errors.business_type_id" id="registry-customer-form-business_type_id-error" role="alert">
          {{ form.errors.business_type_id }}
        </div>
      </div>

      <div class="col-span-1 xl:col-span-2">
        <label class="label" for="registry-customer-form-sales_email">E-mail Commerciale</label>
        <input v-model="form.sales_email" type="email" class="input"  id="registry-customer-form-sales_email" :aria-invalid="!!form.errors.sales_email" :aria-describedby="form.errors.sales_email ? 'registry-customer-form-sales_email-error' : undefined" name="sales_email"/>
        <div class="input-error" v-if="form.errors.sales_email" id="registry-customer-form-sales_email-error" role="alert">
          {{ form.errors.sales_email }}
        </div>
      </div>

      <div class="col-span-1 xl:col-span-2">
        <label class="label" for="registry-customer-form-administrative_email">E-mail Amministrativa</label>
        <input v-model="form.administrative_email" type="email" class="input"  id="registry-customer-form-administrative_email" :aria-invalid="!!form.errors.administrative_email" :aria-describedby="form.errors.administrative_email ? 'registry-customer-form-administrative_email-error' : undefined" name="administrative_email"/>
        <div class="input-error" v-if="form.errors.administrative_email" id="registry-customer-form-administrative_email-error" role="alert">
          {{ form.errors.administrative_email }}
        </div>
      </div>

      <div class="col-span-1 xl:col-span-2">
        <label class="label" for="registry-customer-form-certified_email">PEC</label>
        <input v-model="form.certified_email" type="email" class="input"  id="registry-customer-form-certified_email" :aria-invalid="!!form.errors.certified_email" :aria-describedby="form.errors.certified_email ? 'registry-customer-form-certified_email-error' : undefined" name="certified_email"/>
        <div class="input-error" v-if="form.errors.certified_email" id="registry-customer-form-certified_email-error" role="alert">
          {{ form.errors.certified_email }}
        </div>
      </div>

      <div class="col-span-1 sm:col-span-2 xl:col-span-6">
        <label class="label" for="registry-customer-form-notes">Note Cliente</label>
        <textarea
          v-model="form.notes"
          class="textarea textarea-bordered w-full"
          rows="4"
          placeholder="Inserisci eventuali note operative sul cliente"
         id="registry-customer-form-notes" :aria-invalid="!!form.errors.notes" :aria-describedby="form.errors.notes ? 'registry-customer-form-notes-error' : undefined" name="notes"/>
        <div class="input-error" v-if="form.errors.notes" id="registry-customer-form-notes-error" role="alert">
          {{ form.errors.notes }}
        </div>
      </div>

      <div class="col-span-1 sm:col-span-2 xl:col-span-6 flex flex-wrap justify-end gap-2"><slot name="actions" /></div>
    </div>
</template>
<script setup>
defineEmits(['address-input'])
const props = defineProps({form:{type:Object,required:true}, managers:Array, businessTypes:Array})
</script>
