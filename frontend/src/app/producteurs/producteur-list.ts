import { Component, OnInit, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { forkJoin, of } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { ApiError } from '../core/api-error';
import { Producteur } from '../core/models';
import { ProducteurService } from '../core/producteur.service';
import { ToastService } from '../core/toast.service';

@Component({
  selector: 'app-producteur-list',
  imports: [RouterLink],
  templateUrl: './producteur-list.html',
})
export class ProducteurList implements OnInit {
  private readonly api = inject(ProducteurService);
  private readonly toast = inject(ToastService);

  // Signaux : quand ils changent, Angular redessine uniquement ce qui en dépend.
  protected readonly producteurs = signal<Producteur[]>([]);
  protected readonly stocks = signal<Record<number, number>>({});
  protected readonly loading = signal(true);
  protected readonly error = signal<string | null>(null);
  protected readonly toDelete = signal<Producteur | null>(null);

  ngOnInit(): void {
    this.load();
  }

  protected load(): void {
    this.loading.set(true);
    this.error.set(null);
    this.api.list().subscribe({
      next: (list) => {
        this.producteurs.set(list);
        this.loading.set(false);
        this.loadStocks(list);
      },
      error: (e) => {
        this.error.set(ApiError.from(e).message);
        this.loading.set(false);
      },
    });
  }

  /** Un appel /stock par producteur, tous lancés en parallèle (forkJoin attend la fin de tous). */
  private loadStocks(list: Producteur[]): void {
    if (list.length === 0) return;
    forkJoin(list.map((p) => this.api.stock(p.id).pipe(catchError(() => of(null))))).subscribe(
      (results) => {
        const map: Record<number, number> = {};
        for (const s of results) {
          if (s) map[s.producteur_id] = s.stock_kg;
        }
        this.stocks.set(map);
      },
    );
  }

  protected askDelete(p: Producteur): void {
    this.toDelete.set(p);
  }

  protected cancelDelete(): void {
    this.toDelete.set(null);
  }

  protected confirmDelete(): void {
    const p = this.toDelete();
    if (!p) return;
    this.api.delete(p.id).subscribe({
      next: () => {
        this.toDelete.set(null);
        this.toast.success(`Producteur « ${p.nom} » supprimé.`);
        this.load();
      },
      error: (e) => {
        this.toDelete.set(null);
        // 409 : le producteur a des livraisons, l'API refuse la suppression.
        this.toast.error(ApiError.from(e).message);
      },
    });
  }
}
