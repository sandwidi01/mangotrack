import { Injectable, signal } from '@angular/core';

export interface Toast {
  id: number;
  type: 'success' | 'error';
  text: string;
}

/** Petites notifications en bas d'écran. L'état est un signal : l'affichage se met à jour tout seul. */
@Injectable({ providedIn: 'root' })
export class ToastService {
  readonly toasts = signal<Toast[]>([]);
  private nextId = 1;

  success(text: string): void {
    this.push('success', text);
  }

  error(text: string): void {
    this.push('error', text);
  }

  dismiss(id: number): void {
    this.toasts.update((list) => list.filter((t) => t.id !== id));
  }

  private push(type: Toast['type'], text: string): void {
    const id = this.nextId++;
    this.toasts.update((list) => [...list, { id, type, text }]);
    setTimeout(() => this.dismiss(id), 4000);
  }
}
