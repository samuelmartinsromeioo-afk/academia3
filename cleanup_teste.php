$c = \App\Models\Cadastro\Cliente::where("email","like","teste.%@snrfit.test")->get();
foreach($c as $x){
  \App\Models\Meta::where("cliente_id",$x->id)->delete();
  \App\Models\MedidaCorporal::where("cliente_id",$x->id)->delete();
  \App\Models\Anamnese::where("cliente_id",$x->id)->delete();
  $x->tokens()->delete();
  $x->delete();
}
echo "removidos: ".$c->count();
