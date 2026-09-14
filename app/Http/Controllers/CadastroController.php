<?php

namespace App\Http\Controllers;

use App\Models\Carro;
use App\Models\Marca;
use App\Models\Pessoa;
use App\Models\Revisao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CadastroController extends Controller
{
    private const MODELS = ['pessoas' => Pessoa::class, 'marcas' => Marca::class, 'carros' => Carro::class, 'revisoes' => Revisao::class];

    private function model(string $entidade): Model
    {
        abort_unless(isset(self::MODELS[$entidade]), 404);

        return new (self::MODELS[$entidade]);
    }

    private function query(string $entidade)
    {
        $query = $this->model($entidade)->newQuery();

        return match ($entidade) {
            'pessoas','marcas' => $query->withCount('carros')->orderBy('nome')->orderBy('id'),
            'carros' => $query->with(['pessoa:id,nome', 'marca:id,nome'])->withCount('revisoes')->orderBy('placa'),
            'revisoes' => $query->with(['carro.pessoa:id,nome', 'carro.marca:id,nome'])->orderByDesc('data_revisao')->orderByDesc('id'),
        };
    }

    public function index(Request $request, string $entidade)
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:150'], 'page' => ['sometimes', 'integer', 'min:1'],
            'pessoa_id' => ['nullable', 'integer', 'min:1'], 'carro_id' => ['nullable', 'integer', 'min:1']]);
        $query = $this->query($entidade);
        if (! empty($data['q'])) {
            $term = '%'.mb_strtolower(trim($data['q'])).'%';
            if (in_array($entidade, ['pessoas', 'marcas'])) {
                $query->whereRaw('LOWER(nome) LIKE ?', [$term]);
            }
            if ($entidade === 'carros') {
                $query->where(fn ($q) => $q->whereRaw('LOWER(placa) LIKE ?', [$term])->orWhereRaw('LOWER(modelo) LIKE ?', [$term]));
            }
            if ($entidade === 'revisoes') {
                $query->whereHas('carro', fn ($q) => $q->whereRaw('LOWER(placa) LIKE ?', [$term]));
            }
        }
        if ($entidade === 'carros' && ! empty($data['pessoa_id'])) {
            $query->where('pessoa_id', $data['pessoa_id']);
        }
        if ($entidade === 'revisoes' && ! empty($data['carro_id'])) {
            $query->where('carro_id', $data['carro_id']);
        }

        return $query->paginate(10)->withQueryString();
    }

    public function show(string $entidade, int $id)
    {
        return $this->query($entidade)->findOrFail($id);
    }

    private function validated(Request $request, string $entidade, ?Model $record = null): array
    {
        $normalized = $request->all();
        foreach (['nome', 'modelo', 'email', 'telefone'] as $field) {
            if (isset($normalized[$field]) && is_string($normalized[$field])) {
                $normalized[$field] = trim($normalized[$field]);
            }
        }
        if (isset($normalized['email']) && is_string($normalized['email'])) {
            $normalized['email'] = mb_strtolower($normalized['email']);
        }
        if (isset($normalized['genero']) && is_string($normalized['genero'])) {
            $normalized['genero'] = strtoupper($normalized['genero']);
        }
        if (isset($normalized['placa']) && is_string($normalized['placa'])) {
            $normalized['placa'] = strtoupper(preg_replace('/[\s-]+/', '', $normalized['placa']));
        }
        if (isset($normalized['telefone']) && is_string($normalized['telefone'])) {
            $normalized['telefone'] = preg_replace('/[\s()+.-]+/', '', $normalized['telefone']);
        }
        $request->merge($normalized);
        $uniqueInsensitive = function (string $table, string $column) use ($record) {
            return function ($attribute, $value, $fail) use ($table, $column, $record) {
                if (! is_string($value)) {
                    return;
                }
                $query = DB::table($table)->whereRaw("LOWER($column) = ?", [mb_strtolower($value)]);
                if ($record) {
                    $query->where('id', '<>', $record->id);
                }
                if ($query->exists()) {
                    $fail('Este valor já está cadastrado.');
                }
            };
        };
        $rules = match ($entidade) {
            'pessoas' => [
                'nome' => ['required', 'string', 'max:150'],
                'genero' => ['required', Rule::in(['M', 'F'])],
                'data_nascimento' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
                'email' => ['required', 'email:rfc', 'max:150', $uniqueInsensitive('pessoa', 'email')],
                'telefone' => ['required', 'string', 'regex:/^[0-9]{10,11}$/'],
            ],
            'marcas' => ['nome' => ['required', 'string', 'max:100', $uniqueInsensitive('marca', 'nome')]],
            'carros' => [
                'pessoa_id' => ['required', 'integer', 'exists:pessoa,id'],
                'marca_id' => ['required', 'integer', 'exists:marca,id'],
                'modelo' => ['required', 'string', 'max:100'],
                'ano' => ['required', 'integer', 'min:1960', 'max:'.(now()->year + 1)],
                'placa' => ['required', 'regex:/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/', Rule::unique('carro', 'placa')->ignore($record?->id)],
            ],
            'revisoes' => [
                'carro_id' => ['required', 'integer', 'exists:carro,id'],
                'data_revisao' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            ],
        };
        $result = $request->validate($rules, [
            'required' => 'Preencha este campo.', 'string' => 'Informe um texto válido.', 'integer' => 'Informe um número inteiro.',
            'email' => 'Informe um e-mail válido.', 'unique' => 'Este valor já está cadastrado.',
            'exists' => 'Selecione um registro existente.', 'in' => 'Selecione uma opção válida.',
            'date_format' => 'Informe uma data válida.', 'before_or_equal' => 'A data não pode estar no futuro.',
            'min' => 'O valor mínimo é :min.', 'max' => 'Limite permitido: :max.',
            'placa.regex' => 'Use uma placa brasileira: ABC1234 ou ABC1D23.',
            'telefone.regex' => 'Informe o DDD e telefone, com 10 ou 11 dígitos.',
        ]);
        // Sem tabela de historico de propriedade, trocar dono alteraria relatorios passados.
        if ($entidade === 'carros' && $record && (int) $record->pessoa_id !== (int) $result['pessoa_id'] && $record->revisoes()->exists()) {
            throw ValidationException::withMessages(['pessoa_id' => 'Não é possível trocar o proprietário de um veículo com revisões registradas.']);
        }

        return $result;
    }

    public function store(Request $request, string $entidade)
    {
        $model = $this->model($entidade);
        $record = $model->create($this->validated($request, $entidade));

        return response()->json($this->query($entidade)->findOrFail($record->id), 201);
    }

    public function update(Request $request, string $entidade, int $id)
    {
        $record = $this->model($entidade)->findOrFail($id);
        $record->update($this->validated($request, $entidade, $record));

        return $this->query($entidade)->findOrFail($id);
    }

    public function destroy(string $entidade, int $id)
    {
        $record = $this->model($entidade)->findOrFail($id);
        if (in_array($entidade, ['pessoas', 'marcas']) && $record->carros()->exists()) {
            return response()->json(['message' => 'Existem veículos vinculados. Remova ou reorganize esses vínculos antes de excluir.'], 409);
        }
        if ($entidade === 'carros' && $record->revisoes()->exists()) {
            return response()->json(['message' => 'Este veículo possui revisões. Exclua as revisões antes de excluir o veículo.'], 409);
        }
        $record->delete();

        return response()->noContent();
    }

    public function options(Request $request, string $entidade)
    {
        abort_unless(in_array($entidade, ['pessoas', 'marcas', 'carros']), 404);
        $data = $request->validate(['q' => ['nullable', 'string', 'max:150'], 'selected' => ['nullable', 'integer', 'min:1']]);
        $field = $entidade === 'carros' ? 'placa' : 'nome';
        $query = $this->model($entidade)->newQuery();
        if (! empty($data['q'])) {
            $query->whereRaw("LOWER($field) LIKE ?", ['%'.mb_strtolower($data['q']).'%']);
        }
        $records = $query->orderBy($field)->limit(50)->get();
        if (! empty($data['selected']) && ! $records->contains('id', (int) $data['selected'])) {
            $selected = $this->model($entidade)->find($data['selected']);
            if ($selected) {
                $records->prepend($selected);
            }
        }

        return $records->map(fn ($r) => ['id' => $r->id, 'label' => $entidade === 'carros' ? "{$r->placa} · {$r->modelo}" : $r->nome]);
    }
}
