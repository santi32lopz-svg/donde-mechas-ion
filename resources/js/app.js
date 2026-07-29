// Bootstrap se importa aquí, y no desde un CDN, porque el POS tiene que abrir
// en el local aunque se caiga internet. Se expone en window para que los
// atributos data-bs-* del layout y de los componentes Livewire lo encuentren.
import * as bootstrap from 'bootstrap';

window.bootstrap = bootstrap;
