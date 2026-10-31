import { ChangeDetectionStrategy, Component, DestroyRef, inject, signal } from '@angular/core';
import { takeUntilDestroyed, toSignal } from '@angular/core/rxjs-interop';
import { FormControl, ReactiveFormsModule } from '@angular/forms';
import { NavigationEnd, Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { filter } from 'rxjs';
import { FALLBACK_MENU_ITEMS, MenuService } from '../../core/services/menu.service';
import { NewsletterSection } from '../../shared/components/newsletter-section/newsletter-section';

@Component({
  selector: 'app-public-layout',
  imports: [
    RouterOutlet,
    RouterLink,
    RouterLinkActive,
    ReactiveFormsModule,
    NewsletterSection,
  ],
  templateUrl: './public-layout.html',
  styleUrl: './public-layout.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class PublicLayout {
  private readonly menuService = inject(MenuService);
  private readonly router = inject(Router);

  protected readonly currentYear = new Date().getFullYear();
  protected readonly today = new Intl.DateTimeFormat('pt-BR', {
    weekday: 'long',
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  }).format(new Date());

  protected readonly menuItems = toSignal(this.menuService.header(), {
    initialValue: FALLBACK_MENU_ITEMS,
  });
  protected readonly breaking = toSignal(this.menuService.breaking(), { initialValue: null });

  protected readonly searchOpen = signal(false);
  protected readonly mobileMenuOpen = signal(false);
  protected readonly searchControl = new FormControl('', { nonNullable: true });

  constructor() {
    this.router.events
      .pipe(
        filter((event) => event instanceof NavigationEnd),
        takeUntilDestroyed(inject(DestroyRef)),
      )
      .subscribe(() => {
        this.mobileMenuOpen.set(false);
        this.searchOpen.set(false);
      });
  }

  protected isExternal(url: string): boolean {
    return /^https?:\/\//i.test(url);
  }

  protected toggleSearch(): void {
    this.searchOpen.update((open) => !open);
  }

  protected toggleMobileMenu(): void {
    this.mobileMenuOpen.update((open) => !open);
  }

  protected submitSearch(): void {
    const q = this.searchControl.value.trim();
    if (q) {
      void this.router.navigate(['/busca'], { queryParams: { q } });
    }
  }
}
