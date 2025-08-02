import { FlatCompat } from '@eslint/eslintrc';
import globals from 'globals';
import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';

// For compatibility with old-style configs:
const compat = new FlatCompat({
  baseDirectory: import.meta.url, // Required to resolve config paths
});

export default [
  js.configs.recommended,
  ...pluginVue.configs['flat/recommended'],
  {
    files: ['resources/**/*.js', 'resources/**/*.vue'],
    languageOptions: {
      parserOptions: {
        ecmaVersion: 2020,
        sourceType: 'module',
      },
      globals: {
        ...globals.browser,
        ref: 'readonly',
        computed: 'readonly',
        watch: 'readonly',
        watchEffect: 'readonly',
      },
    },
    rules: {
      indent: ['error', 2],
      quotes: ['warn', 'single'],
      semi: ['warn', 'never'],
      'no-unused-vars': [
        'error',
        { vars: 'all', args: 'after-used', ignoreRestSiblings: true },
      ],
      'comma-dangle': ['warn', 'always-multiline'],
      'vue/multi-word-component-names': [
        'error',
        {
          ignores: [],
        },
      ],
      'vue/max-attributes-per-line': 'off',
      'vue/no-v-html': 'off',
      'vue/require-default-prop': 'off',
      'vue/singleline-html-element-content-newline': 'off',
      'vue/html-self-closing': [
        'warn',
        {
          html: {
            void: 'always',
            normal: 'always',
            component: 'always',
          },
        },
      ],
      'vue/no-v-text-v-html-on-component': 'off',
    },
  },
  {
    ignores: [
      'build/**/*.js',
      '**/**/*.config.js',
      '*.config.js',
    ],
  },
];
