import { Component, OnInit, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { ApiError } from '../core/api-error';
import { ProducteurService } from '../core/producteur.service';
import { ToastService } from '../core/toast.service';

/** Le même formulaire sert à créer (/producteurs/nouveau) et à modifier (/producteurs/:id/modifier). */
@Component({
  selector: 'app-producteur-form',
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './producteur-form.html',
})
export class ProducteurForm implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly api = inject(ProducteurService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);

  protected readonly id = Number(this.route.snapshot.paramMap.get('id')) || null;
  protected readonly saving = signal(false);
  protected readonly loading = signal(false);
  protected readonly serverError = signal<string | null>(null);
  /** Erreurs 422 renvoyées par l'API, champ par champ. */
  protected readonly fieldErrors = signal<Record<string, string[]>>({});

  // Mêmes règles que ProducteurRequest.php : nom 2 à 100 caractères, village max 100.
  protected readonly form = this.fb.nonNullable.group({
    nom: ['', [Validators.required, Validators.minLength(2), Validators.maxLength(100)]],
    village: ['', [Validators.maxLength(100)]],
  });

  ngOnInit(): void {
    if (this.id) {
      this.loading.set(true);
      this.api.get(this.id).subscribe({
        next: (p) => {
          this.form.patchValue({ nom: p.nom, village: p.village ?? '' });
          this.loading.set(false);
        },
        error: (e) => {
          this.toast.error(ApiError.from(e).message);
          this.router.navigate(['/producteurs']);
        },
      });
    }
  }

  protected submit(): void {
    this.serverError.set(null);
    this.fieldErrors.set({});
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const { nom, village } = this.form.getRawValue();
    // Un village vide est envoyé comme null (le champ est "nullable" côté API).
    const payload = { nom: nom.trim(), village: village.trim() || null };

    this.saving.set(true);
    const call = this.id ? this.api.update(this.id, payload) : this.api.create(payload);
    call.subscribe({
      next: () => {
        this.toast.success(this.id ? 'Producteur modifié.' : 'Producteur créé.');
        this.router.navigate(['/producteurs']);
      },
      error: (e) => {
        const err = ApiError.from(e);
        this.saving.set(false);
        if (err.isValidation) {
          this.fieldErrors.set(err.fieldErrors); // ex. {nom: ["Le nom est déjà utilisé"]}
        } else {
          this.serverError.set(err.message);
        }
      },
    });
  }
}
