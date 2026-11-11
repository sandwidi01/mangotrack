import { Routes } from '@angular/router';
import { ProducteurList } from './producteurs/producteur-list';
import { ProducteurForm } from './producteurs/producteur-form';
import { LivraisonList } from './livraisons/livraison-list';
import { LivraisonForm } from './livraisons/livraison-form';

/** Une route = une URL du navigateur associée à un composant (une "page"). */
export const routes: Routes = [
  { path: '', pathMatch: 'full', redirectTo: 'producteurs' },
  { path: 'producteurs', component: ProducteurList },
  { path: 'producteurs/nouveau', component: ProducteurForm },
  { path: 'producteurs/:id/modifier', component: ProducteurForm },
  { path: 'livraisons', component: LivraisonList },
  { path: 'livraisons/nouvelle', component: LivraisonForm },
  { path: '**', redirectTo: 'producteurs' },
];
