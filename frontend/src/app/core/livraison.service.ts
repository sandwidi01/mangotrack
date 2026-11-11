import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';
import { Livraison, LivraisonPayload, Page } from './models';

@Injectable({ providedIn: 'root' })
export class LivraisonService {
  private readonly http = inject(HttpClient);
  private readonly url = '/api/livraisons';

  /**
   * `observe: 'response'` donne accès à toute la réponse HTTP, pas seulement au corps :
   * on en a besoin pour lire l'en-tête X-Total-Count (nombre total, indispensable à la pagination).
   */
  list(page: number, perPage: number, producteurId?: number | null): Observable<Page<Livraison>> {
    let params = new HttpParams().set('page', page).set('per_page', perPage);
    if (producteurId) {
      params = params.set('producteur_id', producteurId);
    }
    return this.http
      .get<Livraison[]>(this.url, { params, observe: 'response' })
      .pipe(
        map((res) => ({
          items: res.body ?? [],
          total: Number(res.headers.get('X-Total-Count') ?? 0),
        })),
      );
  }

  get(id: number): Observable<Livraison> {
    return this.http.get<Livraison>(`${this.url}/${id}`);
  }

  /** Pas de PUT ni de DELETE : l'API ne les expose pas (une livraison est un historique). */
  create(payload: LivraisonPayload): Observable<Livraison> {
    return this.http.post<Livraison>(this.url, payload);
  }
}
