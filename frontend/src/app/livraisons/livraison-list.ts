import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { ApiError } from '../core/api-error';
import { LivraisonService } from '../core/livraison.service';
import { Livraison, Producteur } from '../core/models';
import { ProducteurService } from '../core/producteur.service';

@Component({
  selector: 'app-livraison-list',
  imports: [RouterLink],
  templateUrl: './livraison-list.html',
})
export class LivraisonList implements OnInit {
  private readonly livraisonApi = inject(LivraisonService);
  private readonly producteurApi = inject(ProducteurService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);

  protected readonly perPage = 10;
  protected readonly livraisons = signal<Livraison[]>([]);
  protected readonly producteurs = signal<Producteur[]>([]);
  protected readonly total = signal(0);
  protected readonly page = signal(1);
  protected readonly filter = signal<number | null>(null);
  protected readonly loading = signal(true);
  protected readonly error = signal<string | null>(null);

  // `computed` : valeur dérivée, recalculée automatiquement quand total() change.
  protected readonly lastPage = computed(() => Math.max(1, Math.ceil(this.total() / this.perPage)));

  ngOnInit(): void {
    this.producteurApi.list().subscribe((list) => this.producteurs.set(list));
    // Le filtre vient de l'URL (?producteur=3), ce qui permet le lien depuis la page Producteurs.
    const fromUrl = Number(this.route.snapshot.queryParamMap.get('producteur'));
    this.filter.set(fromUrl || null);
    this.load();
  }

  protected load(): void {
    this.loading.set(true);
    this.error.set(null);
    this.livraisonApi.list(this.page(), this.perPage, this.filter()).subscribe({
      next: (res) => {
        this.livraisons.set(res.items);
        this.total.set(res.total);
        this.loading.set(false);
      },
      error: (e) => {
        this.error.set(ApiError.from(e).message);
        this.loading.set(false);
      },
    });
  }

  protected onFilter(value: string): void {
    this.filter.set(Number(value) || null);
    this.page.set(1); // un nouveau filtre repart toujours de la page 1
    this.router.navigate([], {
      queryParams: { producteur: this.filter() },
      queryParamsHandling: 'merge',
    });
    this.load();
  }

  protected goTo(page: number): void {
    this.page.set(page);
    this.load();
  }

  protected nomProducteur(id: number): string {
    return this.producteurs().find((p) => p.id === id)?.nom ?? `#${id}`;
  }
}
