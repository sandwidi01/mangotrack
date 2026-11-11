/** Formes des données échangées avec l'API MangoTrack (voir app/Http/Resources côté Laravel). */

export interface Producteur {
  id: number;
  nom: string;
  village: string | null;
}

/** Corps envoyé en POST / PUT : pas d'id, il est attribué par le serveur. */
export interface ProducteurPayload {
  nom: string;
  village: string | null;
}

export type Sens = 'entree' | 'sortie';

export interface Livraison {
  id: number;
  producteur_id: number;
  variete: string;
  poids_kg: number;
  sens: Sens;
}

/** Attention : l'API attend la clé `producteur` (et non `producteur_id`) à la création. */
export interface LivraisonPayload {
  producteur: number;
  variete: string;
  poids_kg: number;
  sens: Sens;
}

export interface Stock {
  producteur_id: number;
  stock_kg: number;
}

/** Une page de livraisons : les données + le total lu dans l'en-tête X-Total-Count. */
export interface Page<T> {
  items: T[];
  total: number;
}
