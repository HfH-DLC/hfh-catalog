# HfH Catalog

A network plugin for Pressbooks that lets super admins publish filterable catalogs of books.

## Setup

Catalogs are available at `/catalogs/{catalog-slug}/`. The URL base `catalogs` can be changed under **Network Admin → Catalogs → Settings**.

## How catalogs are organised

A catalog contains books, filters and an optional description. Each filter (e.g. _Subject_) has filter options (e.g. _Mathematics_, _History_), which are assigned to the books in the catalog.

Filters and filter options belong to a single catalog, so a book can appear in several catalogs with different filter options in each.

Books, filters and filter options appear on the public page in the order set in the admin.

## Index

On the catalog pages, visitors can switch between all catalogs marked **Show on index**. These are listed in the order set on the catalogs page.

## Filtering

Options within a filter are combined with OR, filters with AND.

## Notes

- Archived and deactivated books are not shown on public pages.
- Changing a catalog's slug changes its URL; the old URL is not redirected.
- Each book's dashboard has a **Catalogs** page listing the catalogs the book belongs to.
