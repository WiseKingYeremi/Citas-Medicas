import { Component, effect, inject, OnInit, signal } from '@angular/core';
import {MatTableModule} from '@angular/material/table';
import { MedicoService } from '../../shared/services/medico-service';
import { MedicoModel } from '../../shared/models/medico-model';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatDialogModule, MatDialog } from '@angular/material/dialog';
import { FrmMedico } from '../forms/frm-medico/frm-medico';

@Component({
  imports: [MatTableModule, MatCardModule, MatButtonModule, MatIconModule,
            MatDialogModule
  ],
  selector: 'app-medico-component',
  styleUrl: './medico-component.css',
  templateUrl: './medico-component.html',
})
export class MedicoComponent implements OnInit {
  displayedColumns: string[] = ['id', 'nombre_completo', 'especialidad_nombre', 'licencia', 'telefono', 'botonera'];
  dataSource = signal<MedicoModel[]>([])

  srvMedico = inject(MedicoService);

  dialog = inject(MatDialog);

  constructor() {
    effect(() => {
    const respuesta = this.srvMedico.medicos.value();
    if (respuesta !== undefined) {
      //console.log(respuesta.data);
      this.dataSource.set(respuesta.data);
    }
    });

  }

  public mostrarDialogo(titulo: string, datos? : MedicoModel, info? : boolean){
    const dialogRef = this.dialog.open(FrmMedico, {
      width: '50vw',
      maxWidth: '35rem',
      data: {
        title: titulo,
        data: datos,
        info: info
      },
      disableClose : true
    });
    dialogRef.afterClosed()
    .subscribe((objMedico) => {
        if (objMedico === undefined){
          return;
        }
        console.log('Medico creado', objMedico);
        this.srvMedico.guardar(objMedico)
          .subscribe({
            complete :() =>{
              alert('datos guardados');
          }
        })
    });
  }//Fin de mostrar el dialogo

  ngOnInit(): void {
    this.srvMedico.filtrar( {nombre: '', cedula: '', especialidad: ''});
  }

  imprimir(): void {
    window.print();
  }

  onCreate(): void {
    this.mostrarDialogo('Nuevo Cliente');
    //console.log('crear medico');
  }

  onInfo(id: number): void {
    console.log('info', id);
  }

  onEdit(id: number): void {
    console.log('editar', id);
  }

  onDelete(id: number): void {
    console.log('borrar', id);
  }

  onResetPassword(id: number): void {
    console.log('resetear contraseña', id);
  }
}

function subscribe() {
  throw new Error('Function not implemented.');
}
