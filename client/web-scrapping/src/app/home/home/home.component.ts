import { Component, OnInit } from '@angular/core';
import {FormBuilder, FormGroup, NgForm, Validators} from "@angular/forms";
import {HttpClient} from "@angular/common/http";
import { Router } from '@angular/router';
import {PageClientService} from "../../page-client.service";
import {catchError, finalize, Observable, shareReplay, tap} from "rxjs";
import {Page} from "../../models/page";
import {ViewportScroller} from "@angular/common";


@Component({
  selector: 'app-home',
  templateUrl: './home.component.html',
  styleUrls: ['./home.component.css'],
  providers:[]
})



export class HomeComponent implements OnInit {
  pass: string="";
  protected seedUrl: string = "https://www.w3schools.com/";
  protected maxPages: number = 55;
  protected password:string = "";
  public term: string = "";
  public where: string = "";
  public pages$: Observable<Page[]>|null = null;
  public source$: Observable<string>|null = null;
  public loading: boolean= false;
  public btnDisabled:boolean=false;
  public message = '';
  public whereAll="all";

  constructor(public readonly pageClientService:PageClientService,
              private readonly viewportScroller: ViewportScroller
              )
  {}


  disablee() {
    this.btnDisabled = false
    if (this.maxPages > 500) {
      this.btnDisabled = true
      this.message = 'you can index max 500 pages';
    }
  }

  indexPage(form: NgForm)
  {
    this.loading = true;
    this.pages$ = this.pageClientService.indexPage(this.seedUrl, this.maxPages)
      .pipe(
        tap(() => setTimeout(this.scrollViewport.bind(this), 1000)),
        finalize(() => this.loading = false),
      );
  }


  scrollViewport()
  {
    this.viewportScroller.scrollToAnchor("results");
    const p = this.viewportScroller.getScrollPosition();
    this.viewportScroller.scrollToPosition([p[0], p[1]+700]);
  }


  showSource(){
    this.source$ = this.pageClientService.showSource(this.password);
  }


  searchPage()
  {
    this.loading = true;
    this.pages$=this.pageClientService.searchPage(this.term, this.where)
  .pipe(
    tap(() => setTimeout(this.scrollViewport.bind(this), 1000)),
    finalize(() => this.loading = false),
  );
  }


  resetPage()
  {
    this.pageClientService.resetPage().subscribe(
      () =>
      {
        this.pages$=null;
      }
    );
  }

  listPage()
  {
    this.loading = true;
    this.pages$=this.pageClientService.searchPage('', 'list')
      .pipe(
        catchError(
          (error) => {
            this.message='An error occurred';
            throw error;
          }
        ),
        tap(() => setTimeout(this.scrollViewport.bind(this), 1000)),
        finalize(() => this.loading = false),
      );
  }

  ngOnInit() {}

}
