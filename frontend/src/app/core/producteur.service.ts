import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { Producteur, ProducteurPayload, Stock } from './models';

/**
 * Un service = la seule classe qui connaît les URLs de l'API.
 * Les composants appellent des méthodes ; ils ne construisent jamais d'URL eux-mêmes.
 *
 * L'URL est relative (/api/...) : en développement, proxy.conf.json la redirige vers Laravel.
 * L'API renvoie directement l'objet ou le tableau (JsonResource::withoutWrapping() côté Laravel),
 * sans enveloppe {"data": ...}.
 */
@Injectable({ providedIn: 'root' })
export class ProducteurService {
  private readonly http = inject(HttpClient);
  private readonly url = '/api/producteurs';

  list(): Observable<Producteur[]> {
    return this.http.get<Producteur[]>(this.url);
  }

  get(id: number): Observable<Producteur> {
    return this.http.get<Producteur>(`${this.url}/${id}`);
  }

  create(payload: ProducteurPayload): Observable<Producteur> {
    return this.http.post<Producteur>(this.url, payload);
  }

  update(id: number, payload: ProducteurPayload): Observable<Producteur> {
    return this.http.put<Producteur>(`${this.url}/${id}`, payload);
  }

  /** Réponse 204 sans corps. Peut échouer en 409 si le producteur a des livraisons. */
  delete(id: number): Observable<void> {
    return this.http.delete<void>(`${this.url}/${id}`);
  }

  stock(id: number): Observable<Stock> {
    return this.http.get<Stock>(`${this.url}/${id}/stock`);
  }
}
