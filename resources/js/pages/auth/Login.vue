<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import LoginLayout from '@/layouts/auth/LoginLayout.vue'; // Usando o novo layout que criamos
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

// Formulário agora usa 'username' em vez de 'email'
const form = useForm({
    username: '', 
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <LoginLayout cardTitle="Lagoa GED">
        <Head title="Log in" />

        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600 dark:text-green-400">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div class="grid gap-2">
                <Label for="username" class="text-foreground">Nome de Usuário</Label>
                <Input
                    id="username"
                    type="text"        
                    name="username"
                    required
                    autofocus
                    tabindex="1"
                    v-model="form.username" 
                    placeholder="Seu nome de usuário"
                    class="bg-input border-border placeholder:text-muted-foreground"
                />
                <InputError :message="form.errors.username" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password" class="text-foreground">Senha</Label>
                    
                </div>
                <Input
                    id="password"
                    type="password"
                    name="password"
                    required
                    tabindex="2"
                    autocomplete="current-password"
                    v-model="form.password"
                    placeholder="********"
                    class="bg-input border-border placeholder:text-muted-foreground"
                />
                <InputError :message="form.errors.password" />
            </div>

            <div class="flex items-center">
                <Checkbox id="remember" name="remember" v-model:checked="form.remember" tabindex="3" class="mr-2 border-border" />
                <Label for="remember" class="text-sm text-muted-foreground select-none">Lembrar-me</Label>
            </div>

            <Button type="submit" class="w-full" tabindex="4" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                Entrar
            </Button>

            
        </form>
    </LoginLayout>
</template>