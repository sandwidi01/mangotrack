import { HttpErrorResponse } from '@angular/common/http';

/**
 * Toutes les erreurs de l'API ont la même forme : {"message": "...", "errors": {champ: [messages]}}.
 * Cette classe la remet à plat pour que les composants n'aient pas à fouiller dans HttpErrorResponse.
 */
export class ApiError {
  constructor(
    public readonly status: number,
    public readonly message: string,
    public readonly fieldErrors: Record<string, string[]> = {},
  ) {}

  static from(err: unknown): ApiError {
    if (err instanceof HttpErrorResponse) {
      if (err.status === 0) {
        return new ApiError(0, "Impossible de joindre l'API. Le serveur Laravel est-il démarré ?");
      }
      const body = err.error ?? {};
      return new ApiError(err.status, body.message ?? 'Erreur inattendue', body.errors ?? {});
    }
    return new ApiError(-1, 'Erreur inattendue');
  }

  /** 422 : la validation a échoué, `fieldErrors` dit quel champ pose problème. */
  get isValidation(): boolean {
    return this.status === 422;
  }

  /** 409 : conflit avec une règle métier (stock insuffisant, producteur ayant des livraisons). */
  get isConflict(): boolean {
    return this.status === 409;
  }
}
