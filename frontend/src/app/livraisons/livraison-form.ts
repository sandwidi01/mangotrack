import { Component, OnInit, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { ApiError } from '../core/api-error';
import { LivraisonService } from '../core/livraison.service';
import { Producteur } from '../core/models';
import { ProducteurService } from '../core/producteur.service';
import { ToastService } from '../core/toast.service';

@Component({
  selector: 'app-livraison-form',
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './livraison-form.html',
})
export class LivraisonForm implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly livraisonApi = inject(LivraisonService);
  private readonly producteurApi = inject(ProducteurService);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);

  protected readonly producteurs = signal<Producteur[]>([]);
  protected readonly saving = signal(false);
  protected readonly serverError = signal<string | null>(null);
  protected readonly fieldErrors = signal<Record<string, string[]>>({});

  // Mêmes règles que LivraisonRequest.php.
  protected readonly form = this.fb.group({
    producteur: this.fb.control<number | null>(null, Validators.required),
    variete: this.fb.nonNullable.control('', [Validators.required, Validators.maxLength(100)]),
    poids_kg: this.fb.control<number | null>(null, [Validators.required, Validators.min(0.01)]),
    sens: this.fb.nonNullable.control<'entree' | 'sortie'>('entree', Validators.required),
  });

  ngOnInit(): void {
    this.producteurApi.list().subscribe({
      next: (list) => this.producteurs.set(list),
      error: (e) => this.serverError.set(ApiError.from(e).message),
    });
  }

  protected submit(): void {
    this.serverError.set(null);
    this.fieldErrors.set({});
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const v = this.form.getRawValue();
    this.saving.set(true);
    this.livraisonApi
      .create({
        producteur: Number(v.producteur),
        variete: v.variete.trim(),
        poids_kg: Number(v.poids_kg),
        sens: v.sens,
      })
      .subscribe({
        next: () => {
          this.toast.success('Livraison enregistrée.');
          this.router.navigate(['/livraisons']);
        },
        error: (e) => {
          const err = ApiError.from(e);
          this.saving.set(false);
          if (err.isValidation) {
            this.fieldErrors.set(err.fieldErrors);
          } else {
            // 409 : stock insuffisant pour cette sortie. Le message vient de l'API.
            this.serverError.set(err.message);
          }
        },
      });
  }
}
