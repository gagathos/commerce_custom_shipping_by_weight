<?php

namespace Drupal\commerce_custom_shipping_by_weight\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\physical\MeasurementType;
use Drupal\physical\Weight;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the multiple weight condition for shipments.
 *
 * @CommerceCondition(
 *   id = "shipment_weight_condition",
 *   label = @Translation("Shipment weight"),
 *   category = @Translation("Shipment"),
 *   entity_type = "commerce_shipment",
 * )
 */
class ShipmentWeightCondition extends ConditionBase implements ContainerFactoryPluginInterface {

  protected $stateService;

  /**
   * Constructs a new ShipmentWeightCondition object.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\State\StateInterface $stateService
   *   The Drupal State Service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StateInterface $stateService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->stateService = $stateService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'operator' => '<',
      'weight' => NULL,
    ]
     + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $weight = $this->configuration['condition']['weight'];
    $price = $this->configuration['condition']['price'];
    $enabled = $this->configuration['condition']['enabled'];
    $operator = $this->configuration['condition']['operator'];

    $form['condition'] = [
      '#type' => 'fieldset',
      '#title' => 'Condition',
    ];

    // Show condition group fields only when 'Enabled' checkbox is checked.
    $form['condition']['operator'] = [
      '#type' => 'select',
      '#title' => $this->t('Operator'),
      '#options' => $this->getComparisonOperators(),
      '#default_value' => $operator,
      '#required' => FALSE,
      '#states' => [
        'invisible' => [
          ':input[name="conditions[form][shipment][shipment_weight_multiple_conds][configuration][form][condition_' . $i . '][enabled]"]' => ['checked' => FALSE],
        ],
        'required' => [
          ':input[name="conditions[form][shipment][shipment_weight_multiple_conds][configuration][form][condition_' . $i . '][enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['condition']['weight'] = [
      '#type' => 'physical_measurement',
      '#measurement_type' => MeasurementType::WEIGHT,
      '#title' => $this->t('Weight'),
      '#default_value' => $weight,
      '#required' => FALSE,
      '#states' => [
        'invisible' => [
          ':input[name="conditions[form][shipment][shipment_weight_multiple_conds][configuration][form][condition_' . $i . '][enabled]"]' => ['checked' => FALSE],
        ],
        'required' => [
          ':input[name="conditions[form][shipment][shipment_weight_multiple_conds][configuration][form][condition_' . $i . '][enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

  }

  /**
   * Validates filling of price and weight fields in enabled fieldsets.
   *
   * @param array $form
   *   Configuration form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
  }

  /**
   * Evaluates shipment weight conditions.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Shipment entity object.
   *
   * @return bool
   *   Returns entity evaluation result.
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment */
    $shipment = $entity;
    $weight = $shipment->getWeight();
    if (!$weight) {
      // The conditions can't be applied until the weight is known.
      return FALSE;
    }
    // Get enabled conditions list.
    $condition = $this->getConfiguration();
    // Evaluate matching conditions.
    $condition_unit = $condition['weight']['unit'];

    /** @var \Drupal\physical\Weight $weight */
    $weight = $weight->convert($condition_unit);
    $condition_weight = new Weight($condition['weight']['number'], $condition_unit);
    // Saving condition info to states.
    $this->stateService->set('shipment_' . $shipment->getShippingMethodId() . '-' . $shipment->getOrderId() . '_' . $shipment->id() . '_weight_condition', $condition);

    // Return evaluation results.
    switch ($condition['operator']) {
      case '>=':
        return $weight->greaterThanOrEqual($condition_weight);

      case '>':
        return $weight->greaterThan($condition_weight);

      case '<=':
        return $weight->lessThanOrEqual($condition_weight);

      case '<':
        return $weight->lessThan($condition_weight);

      case '==':
        return $weight->equals($condition_weight);

      default:
        throw new \InvalidArgumentException("Invalid operator {$this->configuration['operator']}");
    }
    return FALSE;
  }

}
