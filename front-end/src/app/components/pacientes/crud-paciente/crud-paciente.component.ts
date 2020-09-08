import { MatStepper } from '@angular/material/stepper';
import { RoutingStateService } from '../../../Services/routing/routing-state.service';
import { HttpParams } from '@angular/common/http';
import { Router, ActivatedRoute } from '@angular/router';
import { TokenService } from '../../../Services/token/token.service';
import { ApiBackRequestService } from '../../../Services/api-back-request.service';
import { Component, OnInit, ViewChild } from '@angular/core';
import * as moment from 'moment';
import { FormGroup, FormControl, Validators } from '@angular/forms';
import {map, startWith} from "rxjs/operators";
import Swal from 'sweetalert2';

export interface Ubigeo {
    ubigeo: string,
    distrito: string,
    provincia: string,
    departamento: string,
	  id: number,
}

@Component({
  selector: 'app-crud-paciente',
  templateUrl: './crud-paciente.component.html',
  styleUrls: ['./crud-paciente.component.css']
})

export class CrudPacienteComponent implements OnInit {
  
	myControl = new FormControl();

	@ViewChild('stepper') stepper: MatStepper;

  public form = {
    tipo_documento: null,
    nro_documento: null,
    nombres: null,
    apellido_materno: null,
    apellido_paterno: null,
    fecha_nacimiento: moment().format('YYYY-MM-DD'),
    edad: null,
    sexo: null,
    nro_celular: null,
    email: null,
    grupo_sanguineo: null,
    direccion: null,
    referencias: null,
    tipo_paciente: null,
    observaciones: null,
    estado: 1,
    ubigeo_id: null,

    insert_user_id: this.user.me(),
    edit_user_id: null,
    insert: { name: null },
    edit: { name: '' },
    created_at: null,
    updated_at: null,
    ubigeo: { distrito: null, provincia: null, departamento: null }
  };

	public ubigeos: Ubigeo[] = [];

	public ubigeo = {
	  id: null,
		ubigeo: null,
		distrito: null,
		provincia: null,
		departamento: null
	}

  formPaciente: FormGroup;
  showAge;
 
	filteredUbigeo: any;
  public disabled: boolean = false;
  
  public showProgress: boolean = false;

  public id: HttpParams;

  public encuesta_id: HttpParams

  previousUrl: string;

  constructor(
      private api: ApiBackRequestService,
      private user: TokenService,
      private router: Router,
      private activatedRoute: ActivatedRoute,
      private routingState: RoutingStateService) { }

  ngOnInit(): void {
    this.activatedRoute.queryParams.subscribe(async params => {
      this.id = params.id;
      this.encuesta_id = params.encuesta_id;
      let tab = params.tab;
			if (this.id != null) {
				if (tab != null) {
					this.cargarEditar(1);
				}
				else {
					this.cargarEditar();
				}
			}
    });
    
    this.previousUrl = this.routingState.getPreviousUrl();
    this.validarDatos();
    this.ageCalculator();
    this.fetch();
  }

  validarDatos() {
    this.formPaciente = new FormGroup({
      'nrodni': new FormControl('', [
        Validators.required,
        Validators.minLength(8),
        Validators.maxLength(8),
        Validators.pattern('[0-9]{8,8}')
      ]),
      'nroruc': new FormControl('', [
        Validators.required,
        Validators.minLength(11),
        Validators.maxLength(11),
        Validators.pattern('[0-9]{11,11}')
      ]),
      'nombres': new FormControl('', [
        Validators.required,
        Validators.minLength(3),
        Validators.pattern('[a-zA-Z]{3,254}')
      ]),
      'apepaterno': new FormControl('', [
        Validators.required,
        Validators.minLength(3),
        Validators.pattern('[a-zA-Z]{3,254}')
      ]),
      'apematerno': new FormControl('', [
        Validators.required,
        Validators.minLength(3),
        Validators.pattern('[a-zA-Z]{3,254}')
      ]),
      'nrocel': new FormControl('', [
        Validators.required,
        Validators.minLength(9),
        Validators.maxLength(9),
        Validators.pattern('[0-9]{9,9}')
      ]),
      'email': new FormControl('', [
        Validators.required,
        Validators.pattern('[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,3}$')
      ]),
      'gruposan': new FormControl('', [
        Validators.maxLength(4),
        Validators.pattern('[a-zA-Z+-]{1,4}')
      ]),
      'direccion': new FormControl('', [
        Validators.maxLength(250)
      ]),
      'refer': new FormControl('', [
        Validators.maxLength(250)
      ]),
      'obs': new FormControl('', [
        Validators.maxLength(250)
      ])
    });
  }
  
  ageCalculator(){
    if(this.form.fecha_nacimiento){
      const convertAge = new Date(this.form.fecha_nacimiento);
      const timeDiff = Math.abs(Date.now() - convertAge.getTime());
      this.showAge = Math.floor((timeDiff / (1000 * 3600 * 24))/365);
    }
  }

  async cargarEditar(next?) {
    await this.api.get('personas', this.id).subscribe(
      (data) => {
        this.form = data;
        this.stepper.selected.completed = true;
          if (next) {
            this.stepper.next();
          }
        }
      );

      await this.api.get('ubigeo', this.form.ubigeo_id).subscribe(
        (data) => {
          this.ubigeo = data;
        }
      );
  
      this.stepper.selected.completed = true;
  
      if (next) {
        this.stepper.next();
      }

  }

  cargarUbigeo(e) {
		this.api.get('ubigeo?search=' + e).subscribe(
			data => {
				this.ubigeos = data;
			}
		);
	}

	async fetch() {
		await this.api.get('ubigeo').toPromise() //ESTO LO PUSE ASINCRONO PARA QUE EL AUTOCOMPLETAR FUNCIONE
			.then(
				(data) => { this.ubigeos = data }
			);

		this.filteredUbigeo = this.myControl.valueChanges.pipe(
			startWith(null),
			map(ubigeo => ubigeo && typeof ubigeo === 'object' ? ubigeo.departamento : ubigeo),
			map(ubigeo => ubigeo && typeof ubigeo === 'object' ? ubigeo.provincia : ubigeo),
			map(ubigeo => ubigeo && typeof ubigeo === 'object' ? ubigeo.distrito : ubigeo),
			map(ubigeo => this.filterStates(ubigeo))
		);
	}

	filterStates(val) {
		return val ? this.ubigeos.filter(s => s.departamento.toLowerCase().indexOf(val.toLowerCase()) != -1)
			: this.ubigeos;
		return val ? this.ubigeos.filter(s => s.provincia.toLowerCase().indexOf(val.toLowerCase()) != -1)
			: this.ubigeos;
		return val ? this.ubigeos.filter(s => s.distrito.toLowerCase().indexOf(val.toLowerCase()) != -1)
			: this.ubigeos;
	}

	displayFn(ubigeo): string {
		return ubigeo ? ubigeo.departamento : ubigeo;
		return ubigeo ? ubigeo.provincia : ubigeo;
		return ubigeo ? ubigeo.distrito : ubigeo;
	}

	limpiar() {
		this.form.ubigeo_id = null;
		this.showProgress = false;
	}

	limpiarAutocomplete(e) {
		console.log('kasumi', e.target.value);
		if (e.target.value.length > 3) {
			this.cargarUbigeo(e.target.value);
		}
	}

  guardar() {
    if (this.id) {
      this.editar();
    }
    else {
      this.registrar();
    }
  }

  registrar() {
    if (this.formPaciente.valid) {
      console.log(this.formPaciente.value);
      this.form.ubigeo_id = this.ubigeo.id;
      this.api.post('personas', this.form).subscribe(
        (data) => {
          this.return()
          }
      );
    } else{
      Swal.fire({
        title: 'Complete los datos correctamente',
        icon: 'warning',
        showCancelButton: false,
        cancelButtonColor: '#3085d6',
        cancelButtonText: 'OK'
      })
    }
  }

  editar() {
    this.form.edit_user_id = this.user.me();
    this.form.ubigeo_id = this.ubigeo.id;
    
    this.api.put('personas', this.id, this.form).subscribe(
      (data) => {
        this.return()
        }
      );
  }

  return() {
    if (this.previousUrl.includes('encuesta')) {
      if (this.previousUrl.includes('detalle')) {
        this.router.navigateByUrl('detalle-encuesta?id=' + this.encuesta_id + '&tab=1');
      }
      else {
        this.router.navigateByUrl('crud-encuesta?id=' + this.encuesta_id + '&tab=1');
      }
    }
    else {
      this.router.navigateByUrl(this.previousUrl);
    }
  }

}
