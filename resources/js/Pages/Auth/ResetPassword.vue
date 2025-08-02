<template>
    <div class="reset-password">
      <form @submit.prevent="submit">
        <div class="flex flex-col gap-4">
            <span class="text-lg font-medium">Reset Your Password</span>
            <input
            v-model="form.email"
            type="email"
            placeholder="La tua e-mail"
            class="input"
            required
            />
            <span class="input-error" v-if="form.errors.email">{{ form.errors.email }}</span>

            <input
            v-model="form.password"
            type="password"
            placeholder="Nuova Password"
            class="input"
            required
            />
            <input
            v-model="form.password_confirmation"
            type="password"
            placeholder="Conferma Password"
            class="input"
            required
            />
            <button type="submit" class="btn btn-primary text-lg">Destroy, Erase, Improve!</button>
        </div>

      </form>
    </div>
  </template>
  
  <script>
  import { useForm } from '@inertiajs/vue3';
  
  export default {
    props: {
      token: {
        type: String,
        required: true,
      },
    },
    setup(props) {
      const form = useForm({
        email: '',
        password: '',
        password_confirmation: '',
        token: props.token,
      });
  
      const submit = () => {
        form.post(route('password.update'));
      };
  
      return { form, submit };
    },
  };
  </script>
  
  <style scoped>
  .reset-password {
    max-width: 400px;
    margin: 0 auto;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }
  </style>
  