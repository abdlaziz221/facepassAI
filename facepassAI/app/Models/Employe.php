<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Employe extends Model
{
    use HasFactory;

    protected $fillable = [
        'matricule', 'nom', 'prenom', 'email', 'password', 'role',
        'poste', 'departement', 'salaire_brut', 'photo_faciale', 'est_actif'
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
        'salaire_brut' => 'float',
    ];

    // Mutateur pour hasher automatiquement le mot de passe
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    public function pointages()
    {
        return $this->hasMany(Pointage::class);
    }

    public function demandesAbsence()
    {
        return $this->hasMany(DemandeAbsence::class);
    }
}
