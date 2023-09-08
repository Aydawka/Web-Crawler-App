import { Injectable } from '@angular/core';
import {HttpClient} from "@angular/common/http";
import {filter, map, Observable, tap} from "rxjs";
import {Page} from "./models/page";


@Injectable({
  providedIn: 'root'
})

export class PageClientService {

  public page:Page[]=[];

  constructor(private readonly http:HttpClient) {

  }
  ngOnInit() {
  }

  public indexPage(seedUrl: string, maxPages: number): Observable<Page[]> {

    return this.http.post<any[]>("server/index.php/save",
  {
          seedUrl: seedUrl,
          maxRows: maxPages
      })
      .pipe(
        map((data: any[]): Page[] => data.map(
          (item: any): Page =>
          {
            return new Page(item.url, item.title, item.keywords, item.description);
          }
        )),
        tap(p=>console.log(p))
      );

  }

  public  searchPage(term:string,where: string) {
   return this.http.post<any[]>("server/index.php/search",/*"../../../../server/index.php/search"*/
      {
        where:where,
        term: term
      })
      .pipe(
      map((data: any[]): Page[] => data.map(
        (item: any): Page =>
        {
          return new Page(item.url, item.title, item.keywords, item.description);
        }
      )),
      tap(()=>console.log(where))
    );

  }

  public  resetPage(): Observable<void> {
    return this.http.post<any>(
      "server/index.php/reset",
      {}
    ).pipe(
        map((response: any) => undefined)
    );
  }

public showSource(pass:string): Observable<string>{
  return this.http.post<string>(
    "server/index.php/source",
    {
      password:pass
    }
  );
}

  }

